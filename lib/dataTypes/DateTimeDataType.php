<?php

namespace Lib\dataTypes;

/* This class is derrived from DateDataType and most of the methods are defined in that class. */

use DB;
use Exception;
use Schema;

class DateTimeDataType extends DateDataType
{
    public function getDefaultComparisonType($inputType, $value, $isPrimaryKey)
    {
        if (is_array($value)) {
            if (array_key_exists('min', $value) || array_key_exists('max', $value)) {
                return 'minMax';
            } else {
                return 'matchAny';
            }
        } else {
            return strlen($value) == 19 ? 'equals' : 'startsWith'; // so people can search for partial datetime values such as just YYYY-MM-DD
        }
    }

    /* Queries */

    public function searchQuery($query, $value, $comparisonType, $specialSearch = false)
    {
        if (is_array($value) && $comparisonType == 'minMax') {
            // For minMax searches, convert YYYY-MM-DD dates to specific times to get all records for those dates
            if (@$value['min'] != '' && strlen($value['min']) == 10) {
                $value['min'] .= ' 00:00:00';
            }
            if (@$value['max'] != '' && strlen($value['max']) == 10) {
                $value['max'] .= ' 23:59:59';
            }
        }

        return parent::searchQuery($query, $value, $comparisonType, $specialSearch);
    }
}
