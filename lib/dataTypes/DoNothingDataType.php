<?php

namespace Lib\dataTypes;

/*

Used when we don't want the field to touch the database or do anything else (but when it's used, we may sometimes use closures in the fieldInfo to perform some actions for some functions).

*/

class DoNothingDataType extends DataType
{
    public function createStorage()
    {
    }

    public function removeStorage()
    {
    }

    public function getDefaultComparisonType($inputType, $value, $isPrimaryKey)
    {
        return null;
    }

    public function orderBy($query, $direction)
    {
    }

    public function searchQuery($query, $value, $comparisonType, $specialSearch = false)
    {
    }

    public function addSelect($query, $asFieldName = null)
    {
    }

    public function addAggregateSelect($query, $function, $asFieldName = null)
    {
    }

    public function addLeftJoinIfNeeded($query)
    {
    }

    public function getArrayOfAllValues($query)
    {
        return [];
    }

    public function getCountsOfAllValues($query)
    {
        return [];
    }

    public function getValue($model)
    {
        return null;
    }

    public function setValue($model, $value)
    {
    }

    public function saving($model, $value)
    {
    }

    public function saved($model, $value)
    {
    }
}
