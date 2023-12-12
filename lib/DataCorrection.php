<?php

namespace Lib;

use Exception;
use Illuminate\Support\Facades\DB;

/*

contextValue1, contextValue2 - DataCorrection is often done within the "context" of another field (e.j. each region correction is specific to a particular country).

*/

class DataCorrection extends BaseModel
{
    public static $verbose = false;

    protected $table = 'dataCorrection';

    public static $staticTable = 'dataCorrection'; // just here so we can get the table name without needing an instance of the object

    public $timestamps = false; // we don't need timestamps for dataCorrection

    public static function fieldInfo()
    {
        return [
            'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
            'dbTable' => ['fieldLabelText' => 'Database Table'],
            'dbField' => ['fieldLabelText' => 'Database Field', 'validation' => 'required'],
            'contextValue1' => ['fieldLabelText' => 'Context Value 1'],
            'contextValue2' => ['fieldLabelText' => 'Context Value 2'],
            'oldValue' => ['fieldLabelText' => 'Old Value', 'validation' => 'required'],
            'newValue' => ['fieldLabelText' => 'New Value'],
        ];
    }

    // The oldValue will match even if the case of it is different (so case gets corrected automatically).
    public static function getCorrectedValue($dbTable, $dbField, $value, $contextValue1 = null, $contextValue2 = null, $caseSensitiveSearch = false)
    {
        $query = self::makeBaseSearchQuery($dbTable, $dbField, $contextValue1, $contextValue2);
        $query = $query->select('newValue');
        if ($caseSensitiveSearch) {
            $query->whereRaw('BINARY oldValue=?', $value);
        } else {
            $query->where('oldValue', $value);
        }
        $result = $query->first();

        if (self::$verbose) {
            debugOutput("DataCorrection::correctValue($dbTable, $dbField, $value, $contextValue1, $contextValue2)" .
            ' -> ' . ($result ? $result->newValue : '[unchanged]'));
        }

        return $result ? $result->newValue : $value;
    }

    /*
        This searches for oldValue or newValue values that match the $substringSearch string and returns an array of the matching newValues.
    */

    public static function findSubstringCorrectDataMatches($dbTable, $dbField, $substringSearch = '', $contextValue1 = null, $contextValue2 = null, $limit = false)
    {
        $query = self::makeBaseSearchQuery($dbTable, $dbField, $contextValue1, $contextValue2);

        if ($substringSearch != '') {
            $substringSearch = '%' . $substringSearch . '%';
            $query->where(function ($query) use ($substringSearch) {
                $query->where('oldValue', 'LIKE', $substringSearch)->orWhere('newValue', 'LIKE', $substringSearch);
            });
        }
        if ($limit) {
            $query->limit($limit);
        }

        return $query->groupBy('newValue')->pluck('newValue')->all();
    }

    public static function findFuzzyCorrectDataMatches($dbTable, $dbField, $queryString, $contextValue1 = null, $contextValue2 = null, $limit = false)
    {
        if ($queryString == '') {
            return false;
        } // query string required

        $query = self::makeBaseSearchQuery($dbTable, $dbField, $contextValue1, $contextValue2);

        /* (TO DO)
        Wanted to order by relevance, but can't easily because Eloquent::select() doesn't let us pass parameters (it thinks we're passing multiple select fields)
        $query->select(DB::raw('MATCH(newValue, oldValue) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance'), [ $queryString ])->orderBy('relevance');
        */

        $query->whereRaw('MATCH(newValue, oldValue) AGAINST (? IN NATURAL LANGUAGE MODE)', [$queryString]);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->groupBy('newValue')->pluck('newValue')->all();
    }

    public static function saveCorrection($dbTable, $dbField, $oldValue, $newValue, $contextValue1 = null, $contextValue2 = null, $returnInsertAsArray = false)
    {
        if ($oldValue == '') {
            throw new Exception("The oldValue probably shouldn't be empty.");
        }

        // Change existing records for the $oldValue to the $newValue
        self::makeBaseSearchQuery($dbTable, $dbField, $contextValue1, $contextValue2)->whereRaw('BINARY newValue=?', [$oldValue])->update(['newValue' => $newValue]);

        // If new and old value are the same, we're only recording it so we know this value is ok, so any record with that newValue is all we need...
        if ($newValue == $oldValue && self::makeBaseSearchQuery($dbTable, $dbField, $contextValue1, $contextValue2, null, $newValue)->exists()) {
            return null;
        }

        // Otherwise we need an exact match of the same oldValue/newValue...
        if (self::makeBaseSearchQuery($dbTable, $dbField, $contextValue1, $contextValue2, $oldValue, $newValue)->exists()) {
            return null;
        }

        $insert = ['dbTable' => $dbTable, 'dbField' => $dbField, 'oldValue' => $oldValue, 'newValue' => $newValue,
            'contextValue1' => (string) $contextValue1, 'contextValue2' => (string) $contextValue2, ];
        if ($returnInsertAsArray) {
            return $insert;
        }
        self::insert($insert);
    }

    public static function correctAllDatabaseValues($dbTable, $dbField, $queryBasis = null,
        $actualTableToUpdate = null, $actualFieldToUpdate = null, $contextField1 = null, $contextField2 = null)
    {
        $output = '';

        if ($actualTableToUpdate == '') {
            $actualTableToUpdate = $dbTable;
        }
        if ($actualFieldToUpdate == '') {
            $actualFieldToUpdate = $dbField;
        }
        if (! $queryBasis) {
            $queryBasis = DB::table($actualTableToUpdate);
        }

        $corrections = self::makeBaseSearchQuery($dbTable, $dbField)->whereRaw('BINARY ' . self::$staticTable . '.oldValue != BINARY ' . self::$staticTable . '.newValue')
            ->join($actualTableToUpdate, function ($join) use ($actualTableToUpdate, $actualFieldToUpdate, $contextField1, $contextField2) {
                $join->on(DB::raw('BINARY ' . self::$staticTable . '.oldValue'), '=', DB::raw("BINARY $actualTableToUpdate.$actualFieldToUpdate"));
                if ($contextField1 !== null) {
                    $join->on(self::$staticTable . '.contextValue1', '=', "$actualTableToUpdate.$contextField1");
                }
                if ($contextField2 !== null) {
                    $join->on(self::$staticTable . '.contextValue2', '=', "$actualTableToUpdate.$contextField2");
                }
            })
            ->groupBy(DB::raw('BINARY ' . self::$staticTable . '.oldValue'), DB::raw('BINARY ' . self::$staticTable . '.newValue'), self::$staticTable . '.contextValue1', self::$staticTable . '.contextValue2')
            ->select(self::$staticTable . '.*')->get();

        foreach ($corrections as $correction) {
            $output .= "[ $correction->contextValue1 $correction->contextValue2 '$correction->oldValue' -> '$correction->newValue' ] ";
            $query = clone $queryBasis;
            if ($contextField1 !== null) {
                $query->where($contextField1, $correction->contextValue1);
            }
            if ($contextField2 !== null) {
                $query->where($contextField2, $correction->contextValue2);
            }
            $query->whereRaw("BINARY $actualFieldToUpdate=?", [$correction->oldValue])->update([$actualFieldToUpdate => $correction->newValue]);
        }

        return $output;
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'monthly':
                $output .= "\nRemove dataCorrection Duplicates: ";
                $duplicateRows = self::select(DB::raw('*, count(*) as count'))
                    ->groupBy(DB::raw('BINARY oldValue'), 'dbTable', 'dbField', 'contextValue1', 'contextValue2')
                    ->having('count', '>', 1)->get();
                foreach ($duplicateRows as $row) {
                    $output .= "[$row->dbField '$row->oldValue'/'$row->newValue' count: $row->count] ";
                    // Note this only deletes one of the duplicates.  If there are more than 2, we'll get the others next time.
                    self::where('id', $row->id)->delete();
                }

                $output .= "\nRemove Unneeded Equal Value Rows: ";
                // If new and old value are the same, we're only recording it so we know this value is ok, so any record with that newValue is all we need...
                $equalValueRows = self::whereRaw('BINARY oldValue = BINARY newValue')->get();
                foreach ($equalValueRows as $equalValueRow) {
                    if (self::makeBaseSearchQuery($equalValueRow->dbTable, $equalValueRow->dbField,
                        $equalValueRow->contextValue1, $equalValueRow->contextValue2, null, $equalValueRow->newValue)
                            ->whereRaw('BINARY oldValue != ?', [$equalValueRow->oldValue])->exists()) {
                        $output .= "[delete $equalValueRow->id] ";
                        self::where('id', $equalValueRow->id)->delete();
                    }
                }

                $output .= "\nOptimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);
                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    /* Private Functions */

    private static function makeBaseSearchQuery($dbTable, $dbField, $contextValue1 = null, $contextValue2 = null, $oldValue = null, $newValue = null)
    {
        $query = self::where(self::$staticTable . '.dbTable', $dbTable)->where(self::$staticTable . '.dbField', $dbField);
        if ($contextValue1 !== null) {
            $query->where(self::$staticTable . '.contextValue1', $contextValue1);
        }
        if ($contextValue2 !== null) {
            $query->where(self::$staticTable . '.contextValue2', $contextValue2);
        }
        if ($oldValue !== null) {
            $query->whereRaw('BINARY ' . self::$staticTable . '.oldValue=?', [$oldValue]);
        }
        if ($newValue !== null) {
            $query->whereRaw('BINARY ' . self::$staticTable . '.newValue=?', [$newValue]);
        }

        return $query;
    }
}
