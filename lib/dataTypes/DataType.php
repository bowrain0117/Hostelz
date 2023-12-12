<?php

namespace Lib\dataTypes;

use DB;
use Exception;
use Schema;

/*
    BaseDataType base class
*/

abstract class DataType
{
    public $fieldName;

    public $tableName;

    public $keyFieldName;

    public $firstTableKeyFieldname;

    public function __construct($initialValues)
    {
        $this->tableName = @$initialValues['tableName'];
        $this->fieldName = @$initialValues['fieldName'];
        $this->firstTableKeyFieldname = @$initialValues['firstTableKeyFieldname'];
        $this->keyFieldName = @$initialValues['keyFieldName'] ?: 'id'; // defaults to 'id'
    }

    /* Create / Remove Storage */

    /* public function createStorage()  - Can be used if needed. Called by BaseModel when createTables() is called. */
    /* public function removeStorage()  - Can be used if needed. Called by BaseModel when dropTables() is called. */

    /* Queries */

    public function getDefaultComparisonType($inputType, $value, $isPrimaryKey)
    {
        if (is_array($value)) {
            if (array_key_exists('min', $value) || array_key_exists('max', $value)) {
                return 'minMax';
            } else {
                return 'matchAny';
            }
        } elseif (! $isPrimaryKey && in_array($inputType, ['', 'text', 'datePicker', 'WYSIWYG', 'textarea', 'url', 'email'])) {
            return 'substring';
        } else {
            return 'equals';
        }
    }

    public function orderBy($query, $direction)
    {
        $query->orderBy($this->getFullFieldName(), $direction);
        $this->addLeftJoinIfNeeded($query);
    }

    public function searchQuery($query, $value, $comparisonType, $specialSearch = false)
    {
        if (! in_array($comparisonType, ['isEmpty', 'notEmpty', 'emptyOrEquals']) &&
            ($value === null || $value === '')) {
            return $query;
        } // ignore empty search values and arrays

        if (is_array($value)) {
            switch ($comparisonType) {
                case 'minMax':
                    if (array_key_exists('min', $value) && array_key_exists('max', $value) && $value['min'] !== '' && $value['max'] !== '') {
                        $query->whereBetween($this->getFullFieldName(), [$value['min'], $value['max']]);
                    } elseif (array_key_exists('min', $value) && $value['min'] !== '') {
                        $query->where($this->getFullFieldName(), '>=', $value['min']);
                    } elseif (array_key_exists('max', $value) && $value['max'] !== '') {
                        $query->where($this->getFullFieldName(), '<=', $value['max']);
                    }
                    break;

                case 'matchAny':
                    if ($value) {
                        $query->whereIn($this->getFullFieldName(), $value);
                    }
                    break;

                default:
                    throw new Exception("Unknown comparisonType for an array value '$comparisonType'.");
            }
        } else {
            switch ($comparisonType) {
                case 'equals':
                case 'emptyOrEquals':
                    $operator = '=';
                    $wildcardValue = $value;
                    $phoenticQuery = '(SOUNDEX(' . $this->getFullFieldName() . ') = SOUNDEX(?) OR ' . $this->getFullFieldName() . ' = ?)';
                    break;

                case 'notEqual':
                case 'emptyOrNotEqual':
                    $operator = '!=';
                    $wildcardValue = $value;
                    $phoenticQuery = '(SOUNDEX(' . $this->getFullFieldName() . ') != SOUNDEX(?) AND ' . $this->getFullFieldName() . ' != ?)';
                    break;

                case 'startsWith':
                    $operator = 'LIKE';
                    $wildcardValue = $value . '%';
                    $phoenticQuery = '(TRIM(TRAILING "0" FROM SOUNDEX(' . $this->getFullFieldName() . ')) LIKE CONCAT(TRIM(TRAILING "0" FROM SOUNDEX(?)), "%") OR ' . $this->getFullFieldName() . ' LIKE ?)';
                    break;

                case 'substring':
                    $operator = 'LIKE';
                    $wildcardValue = '%' . $value . '%';
                    $phoenticQuery = '(TRIM(TRAILING "0" FROM SOUNDEX(' . $this->getFullFieldName() . ')) LIKE CONCAT("%", TRIM(TRAILING "0" FROM SOUNDEX(?)), "%") OR ' . $this->getFullFieldName() . ' LIKE ?)';
                    break;

                case 'notSubstring':
                    $operator = 'NOT LIKE';
                    $wildcardValue = '%' . $value . '%';
                    $phoenticQuery = '(TRIM(TRAILING "0" FROM SOUNDEX(' . $this->getFullFieldName() . ')) NOT LIKE CONCAT("%", TRIM(TRAILING "0" FROM SOUNDEX(?)), "%") AND ' . $this->getFullFieldName() . ' NOT LIKE ?)';
                    break;

                case 'isEmpty':
                    $operator = '=';
                    $wildcardValue = '';
                    $phoenticQuery = null;
                    break;

                case 'notEmpty':
                    $operator = '!=';
                    $wildcardValue = '';
                    $phoenticQuery = null;
                    break;

                default:
                    throw new Exception("Unknown comparisonType '$comparisonType' for a non-array value.");
            }

            switch ($specialSearch) {
                case 'phoentic':
                    if ($phoenticQuery != null) {
                        $query->whereRaw($phoenticQuery, [$value, $wildcardValue]);
                        break;
                    }
                    // (no break here)

                default:
                    $query->where($this->getFullFieldName(), $operator, $wildcardValue);
                    break;
            }
        }

        $this->addLeftJoinIfNeeded($query);

        return $query; // the query is the return value because it's useful for chaining.
    }

    /* Other Database Functions */

    // Make the query select the data for this field (note that this is only used for some cases, such as results lists when we don't want to fetch the whole row)

    public function addSelect($query, $asFieldName = null)
    {
        $query->addSelect($this->getFullFieldName() . ($asFieldName !== null ? ' as ' . $asFieldName : ''));
        $this->addLeftJoinIfNeeded($query);
    }

    public function addAggregateSelect($query, $function, $asFieldName = null)
    {
        switch ($function) {
            case 'min':
                $select = DB::raw('MIN(' . $this->getFullFieldName() . ')' . ($asFieldName !== null ? ' AS ' . $asFieldName : ''));
                break;

            case 'max':
                $select = DB::raw('MAX(' . $this->getFullFieldName() . ')' . ($asFieldName !== null ? ' AS ' . $asFieldName : ''));
                break;

            default:
                throw new Exception("Unknown function '$function'.");
        }

        $query->addSelect($select);
        $this->addLeftJoinIfNeeded($query);
    }

    // Add a left join to the query to get foreign table values (if needed)

    public function addLeftJoinIfNeeded($query)
    {
        if (method_exists($query, 'toBase')) {
            $query = $query->toBase();
        } // if it's an Eloquent model, get the underlying query.
        if ($this->tableName == '' || $query->from == $this->tableName) {
            return;
        } // not a foreign table, nothing we need to do.
        if ($query->joins && searchArrayForProperty($query->joins, 'table', $this->tableName)) {
            return;
        } // this left join was already added
        $query->leftJoin($this->tableName, $this->getKeyFullFieldName(), '=', $query->from . '.' . $this->firstTableKeyFieldname);
    }

    public function getArrayOfAllValues($query)
    {
        // This may not work with foreign table fields (not tested)
        $queryTemp = clone $query;

        return $queryTemp->select($this->getFullFieldName())->where($this->getFullFieldName(), '!=', '')
            ->groupBy($this->getFullFieldName())->orderBy($this->getFullFieldName())->pluck($this->getFullFieldName())->all();
    }

    public function getCountsOfAllValues($query)
    {
        // This may or may not not work with foreign table fields (not tested)
        $queryTemp = clone $query;

        return $queryTemp->select([DB::raw('count(*) AS count'), $this->getFullFieldName() . ' as GET_COUNTS_FIELD'])->where($this->getFullFieldName(), '!=', '')
            ->groupBy($this->getFullFieldName())->orderBy($this->getFullFieldName())->pluck('count', 'GET_COUNTS_FIELD')->all();
    }

    /* Get / Set */

    public function getValue($model)
    {
        debugOutput("DataType {$this->getFullFieldName()} getValue().");

        if ($this->firstTableKeyFieldname != '') {
            // This means it's a foreign field, so we need to get it from the database...
            $firstTableKeyValue = $model->{$this->firstTableKeyFieldname};
            if (! $firstTableKeyValue) {
                return null;
            } // the firstTable's key field value wasn't set to anything (usually should have been an id value)

            return DB::table($this->tableName)->where($this->getKeyFullFieldName(), $firstTableKeyValue)->value($this->fieldName);
        } else {
            // Just use the actual attribute from the model.
            // (have to use getActualAttribute() so we don't end up in a loop since this getValue() function may have been called by the model's __get() magic method).
            return $model->getActualAttribute($this->fieldName);
        }
    }

    /* setValue()? -> Note: There isn't a setValue() function because that kind of functionality is done using the saving() and saved() event responders below. */

    /* Respond to event triggers... */

    /* public function delete($model) - Can be used if needed. */

    public function saving($model, $value)
    {
        debugOutput("DataType {$this->getFullFieldName()} saving([model], $value).");

        // If it's in the model (not a foreign table), just set the value in the model
        // (for foreign tables, we do that after the model was saved in the saved() function below.)
        if ($this->firstTableKeyFieldname == '') {
            $model->setActualAttribute($this->fieldName, $value);
        }
    }

    public function saved($model, $value)
    {
        debugOutput("DataType {$this->getFullFieldName()} saved([model], " . print_r($value, true) . ').');

        // (For normal, non-foreign table values, we don't have to do anything since the value gets saved with the model already.)

        if ($this->firstTableKeyFieldname != '') {
            // This means it's a foreign field, so we need to set it in the database...
            $firstTableKeyValue = $model->{$this->firstTableKeyFieldname};
            if (! $firstTableKeyValue) {
                throw new Exception("firstTableKeyValue ('{$this->firstTableKeyFieldname}') of the model not set.");
            }
            // echo "DB::table({$this->tableName})->where({$this->getKeyFullFieldName()}, $firstTableKeyValue)->update([ {$this->getFullFieldName()} => $value ]);";
            DB::table($this->tableName)->where($this->getKeyFullFieldName(), $firstTableKeyValue)->update([$this->getFullFieldName() => $value]);
        }
    }

    /* Protected Functions */

    protected function getFullFieldName()
    {
        return ($this->tableName != null ? $this->tableName . '.' : '') .
            $this->fieldName;
    }

    protected function getKeyFullFieldName()
    {
        if ($this->keyFieldName == '') {
            throw new Exeption('keyFieldName not set for this dataType field.');
        }

        return ($this->tableName != null ? $this->tableName . '.' : '') .
            $this->keyFieldName;
    }
}
