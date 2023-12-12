<?php

namespace App\Models;

use App\Traits\PlaceFields;
use Exception;
use Lib\BaseModel;

class SearchRank extends BaseModel
{
    use PlaceFields;

    protected $table = 'searchRanks';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'checkDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 10],
                    'source' => ['maxLength' => 50],
                    'searchPhrase' => ['maxLength' => 200],
                    'rank' => ['maxLength' => 10, 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'placeID' => ['type' => 'ignore'], // just here so we can make URLs that search by place
                    'placeType' => ['type' => 'ignore'], // just here so we can make URLs that search by place
                    'placeSelector' => self::placeSelectorFieldInfo(),
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $return;
    }

    /* Accessors & Mutators */

    /* Static */

    /* Misc */

    /* Scopes */

    /* Relationships */
}
