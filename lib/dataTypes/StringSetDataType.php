<?php

namespace Lib\dataTypes;

use DB;
use Exception;
use Schema;

/*

This data type handles database fields that are a set of strings.

Currently it's implemeted using MySQL's FIND_IN_SET() functions,
but if we need to support other databases that don't have these functions,
this could be modified to use a separate table, or to still store them as CSV,
but use special search functions to search them.

*/

class StringSetDataType extends DataType
{
    protected $allPossibleValues; // array of all possible values for the set (optional).

    protected $maxTotalCharacters;

    public function __construct($initialValues)
    {
        $this->allPossibleValues = @$initialValues['allPossibleValues'];
        $this->maxTotalCharacters = @$initialValues['maxTotalCharacters'] ?: 2000; // defaults to 2000

        parent::__construct($initialValues);
    }

    public function createStorage()
    {
        if (! Schema::hasColumn($this->tableName, $this->fieldName)) {
            DB::statement('ALTER TABLE ' . $this->tableName . ' ADD `' . $this->fieldName . '` VARCHAR(' . $this->maxTotalCharacters . ') NOT NULL');
        }
    }

    // Note: removeStorage() not needed because the column will get removed when the table gets removed.

    public function getValue($model)
    {
        $value = $model->getActualAttribute($this->fieldName);
        if ($value == '') {
            return [];
        }

        return is_array($value) ? $value : explode(',', $value);
    }

    /* Events */

    public function saving($model, $value)
    {
        $original = $value;
        if ($value === null || $value === '' || $value === ['']) {
            $value = [];
        }

        if (! is_array($value)) {
            $value = [$value];
        } // make it an array if it wasn't.

        // Check to make sure the values are in the $allPossibleValues set.
        if ($this->allPossibleValues && array_diff($value, $this->allPossibleValues)) {
            throw new Exception(implode(', ', array_diff($value, $this->allPossibleValues)) . ' not in allPossibleValues ' . implode(', ', $this->allPossibleValues));
        }

        // If it's in the model (not a foreign table), just set the value in the model
        // (for foreign tables, we do that after the model was saved in the saved() function below.)
        if ($this->firstTableKeyFieldname == '') {
            $model->setActualAttribute($this->fieldName, implode(',', $value));
        }
    }

    public function saved($model, $value)
    {
        // (For normal, non-foreign table values, we don't have to do anything since the value gets saved with the model already.)

        if ($this->firstTableKeyFieldname != '') {
            // This means it's a foreign field, so we need to set it in the database...
            $firstTableKeyValue = $model->{$this->firstTableKeyFieldname};
            if (! $firstTableKeyValue) {
                throw new Exception("firstTableKeyValue ('{$this->firstTableKeyFieldname}') of the model not set.");
            }
            // echo "DB::table({$this->tableName})->where({$this->getKeyFullFieldName()}, $firstTableKeyValue)->update([ {$this->getFullFieldName()} => $value ]);";
            DB::table($this->tableName)->where($this->getKeyFullFieldName(), $firstTableKeyValue)->update([$this->getFullFieldName() => implode(',', $value)]);
        }
    }

    /* Queries */

    public function getDefaultComparisonType($inputType, $value, $isPrimaryKey)
    {
        if ($isPrimaryKey) {
            return 'equals';
        }

        return 'matchAny';
    }

    public function searchQuery($query, $values, $comparisonType, $specialSearch = false)
    {
        if (! is_array($values)) {
            $values = [$values];
        } // make it always an array for simplicity

        // Remove empty array elements (avoids unnecessary do-nothing queries)
        $values = array_filter($values, function ($v) {
            return $v != '';
        });

        if (! $values) {
            return $query;
        } // empty array, ignore

        switch ($comparisonType) {
            case 'matchAll':
                $wheres = array_fill(0, count($values), 'FIND_IN_SET(?, ' . $this->getFullFieldName() . ')');
                $query->whereRaw(implode(' AND ', $wheres), $values);
                break;

            case 'matchNone':
                $wheres = array_fill(0, count($values), '!FIND_IN_SET(?, ' . $this->getFullFieldName() . ')');
                $query->whereRaw(implode(' AND ', $wheres), $values);
                break;

            case 'matchNotAll':
                $wheres = array_fill(0, count($values), '!FIND_IN_SET(?, ' . $this->getFullFieldName() . ')');
                $query->whereRaw('(' . implode(' OR ', $wheres) . ')', $values);
                break;

            case 'matchAny':
                $wheres = array_fill(0, count($values), 'FIND_IN_SET(?, ' . $this->getFullFieldName() . ')');
                $query->whereRaw('(' . implode(' OR ', $wheres) . ')', $values);
                break;

            case 'substring':
                if (count($values) > 1) {
                    throw new Exception('Substring searching in StringSetDataType only implemented for one value.');
                }
                $value = $values[0];
                $operator = 'LIKE';
                $wildcardValue = '%' . $value . '%';
                $query->where($this->getFullFieldName(), $operator, $wildcardValue);
                break;

            default:
                throw new Exception("Unknown comparisonType '$comparisonType'.");
        }

        return $query; // the query is the return value because it's useful for chaining.
    }

    public function getArrayOfAllValues($query)
    {
        $queryTemp = clone $query;
        $sets = $queryTemp->select($this->getFullFieldName())->where($this->getFullFieldName(), '!=', '')
            ->groupBy($this->getFullFieldName())->orderBy($this->getFullFieldName())->pluck($this->fieldName)->all();

        $result = [];
        foreach ($sets as $set) {
            $items = explode(',', $set);
            $result = array_merge($result, $items);
        }

        $result = array_unique($result);
        sort($result);

        return $result;
    }

    public function getCountsOfAllValues($query)
    {
        $queryTemp = clone $query;
        $sets = $queryTemp->select([DB::raw('count(*) AS count'), $this->getFullFieldName() . ' as GET_COUNTS_FIELD'])->where($this->getFullFieldName(), '!=', '')
            ->groupBy($this->getFullFieldName())->orderBy($this->getFullFieldName())->pluck('count', 'GET_COUNTS_FIELD')->all();

        $result = [];
        foreach ($sets as $set => $count) {
            $items = explode(',', $set);
            foreach ($items as $item) {
                if (array_key_exists($item, $result)) {
                    $result[$item] += $count;
                } else {
                    $result[$item] = $count;
                }
            }
        }

        ksort($result);

        return $result;
    }
}
