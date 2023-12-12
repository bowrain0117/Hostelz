<?php

namespace Lib\dataTypes;

/* See also DateTimeDataType for DATETIME values. */

use DB;
use Exception;
use Schema;

class DateDataType extends DataType
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
            return strlen($value) == 10 ? 'equals' : 'startsWith'; // so people can search for partial datetime values such as just YYYY
        }
    }
}
