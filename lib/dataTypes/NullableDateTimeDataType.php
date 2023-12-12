<?php

namespace Lib\dataTypes;

use DB;
use Exception;
use Schema;

class NullableDateTimeDataType extends DateTimeDataType
{
    /* Events */

    public function saving($model, $value)
    {
        if ($value === '') {
            $value = null;
        } // if the user sets the date field value to blank, we save it in the database a NULL

        parent::saving($model, $value);
    }
}
