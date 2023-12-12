<?php

namespace App\Traits;

use App\Helpers\ListingAndCitySearch;
use App\Models\CityInfo;
use App\Models\ContinentInfo;
use App\Models\CountryInfo;
use App\Models\Listing\Listing;
use Exception;
use Request;
use Response;

/*

Handles adding fields to a model that let the model be associated with a "place" (a city, country, listing, etc.)

alter table incomingLinks add column placeType varchar(50) not null
alter table incomingLinks add column placeID int not null
alter table incomingLinks add column placeString varchar(70) not null

*/

trait PlaceFields
{
    /*
       The placeTypes to use with this model can be changed by setting $placeTypes in the model class's definition.
       Note: This must be in order from least to most specific in order for placeSpecificityRank() to work.

       public static $placeTypes = [ 'ContinentInfo', 'CountryInfo', 'Region', 'CityInfo', 'Listing' ];
    */

    protected static function defineDatabasePlaceFields($table, $makeIndex = false): void
    {
        $table->string('placeType', 50);
        $table->integer('placeID');
        $table->string('placeString', 70); // used for ContinentInfo and Region

        if ($makeIndex) {
            $table->index(['placeType', 'placeID', 'placeString']);
        }
    }

    public static function placeSelectorFieldInfo()
    {
        return [
            'dataAccessMethod' => 'none',
            'getValue' => function ($formHandler, $model) {
                return $model->getEncodedPlaceString();
            },
            'setValue' => function ($formHandler, $model, $value): void {
                $model->setTypeAndIdFromEncodedPlaceString($value);
            },
            'searchQuery' => function ($formHandler, $query, $value) {
                return $value == '' ? $query : $query->byEncodedPlaceString($value);
            },
        ];
    }

    public static function handlePlaceIdChange($placeType, $oldPlaceID, $newPlaceID): void
    {
        self::where('placeType', $placeType)->where('placeID', $oldPlaceID)
            ->update(['placeID' => $newPlaceID]);
    }

    public static function handlePlaceSearchAjaxCommand()
    {
        if (Request::input('command') != 'placeSearch') {
            return null;
        }

        $search = Request::input('search');
        if ($search == '') {
            return '';
        }

        $searchResultData = ListingAndCitySearch::performSearch(
            $search,
            15,
            in_array('Listing', self::$placeTypes) ? Listing::areLiveOrNew() : null,
            in_array('CityInfo', self::$placeTypes) ? CityInfo::areLive() : null,
            in_array('CountryInfo', self::$placeTypes) ? CountryInfo::areLive() : null
        );

        $results = [];

        foreach ($searchResultData as $type => $items) {
            foreach ($items as $item) {
                switch ($type) {
                    case 'continents':
                        if (! in_array('ContinentInfo', self::$placeTypes)) {
                            break;
                        }
                        $continent = $item->continent;
                        $results[] = [
                            'text' => $continent,
                            'id' => self::makeEncodedPlaceString('ContinentInfo', 0, $continent),
                        ];

                        break;

                    case 'countries':
                        if (! in_array('CountryInfo', self::$placeTypes)) {
                            break;
                        }
                        $countryInfo = $item;
                        $results[] = [
                            'text' => $countryInfo->country,
                            'id' => self::makeEncodedPlaceString('CountryInfo', $countryInfo->id, ''),
                        ];

                        break;

                    case 'regions':
                        if (! in_array('Region', self::$placeTypes)) {
                            break;
                        }
                        $cityInfo = $item;
                        $countryInfo = $item->countryInfo;
                        if (! $countryInfo) {
                            break;
                        }
                        $results[] = [
                            'text' => $countryInfo->regionFullDisplayName($cityInfo->region),
                            'id' => self::makeEncodedPlaceString('Region', $countryInfo->id, $cityInfo->region),
                        ];

                        break;

                    case 'cities':
                        if (! in_array('CityInfo', self::$placeTypes)) {
                            break;
                        }
                        $cityInfo = $item;
                        $results[] = [
                            'text' => $cityInfo->fullDisplayName(),
                            'id' => self::makeEncodedPlaceString('CityInfo', $cityInfo->id, ''),
                        ];

                        break;

                    case 'listings':
                        if (! in_array('Listing', self::$placeTypes)) {
                            break;
                        }
                        $listing = $item;
                        $results[] = [
                            'text' => $listing->fullDisplayName(),
                            'id' => self::makeEncodedPlaceString('Listing', $listing->id, ''),
                        ];

                        break;
                }
            }
        }

        return Response::json(['results' => $results]);
    }

    public function placeDisplayName()
    {
        switch ($this->placeType) {
            case 'ContinentInfo':
            case 'Region':
                return $this->placeString;

            case 'CountryInfo':
                return $this->getPlaceObjectOrFail()->country;

            case 'CityInfo':
                return $this->getPlaceObjectOrFail()->city;

            case 'Listing':
                return $this->getPlaceObjectOrFail()->name;

            default:
                throw new Exception("Unknown model type '$this->placeType'.");
        }
    }

    public function placeFullDisplayName()
    {
        switch ($this->placeType) {
            case 'ContinentInfo':
                return $this->placeString;

            case 'CountryInfo':
                return $this->getPlaceObjectOrFail()->country;

            case 'Region':
                return $this->getPlaceObjectOrFail()->regionFullDisplayName($this->placeString);

            case 'Listing':
            case 'CityInfo':
                return $this->getPlaceObjectOrFail()->fullDisplayName();

            default:
                throw new Exception("Unknown model type '$this->placeType'.");
        }
    }

    public function placeURL($urlType = 'auto', $language = null, $ignorePoorContent = false)
    {
        switch ($this->placeType) {
            case 'ContinentInfo':
            case 'CountryInfo':
            case 'CityInfo':
                return $this->getPlaceObjectOrFail()->getURL($urlType, $language);

            case 'Region':
                return $this->getPlaceObjectOrFail()->getRegionURL($this->placeString, $urlType, $language);

            case 'Listing':
                return $this->getPlaceObjectOrFail()->getURL($urlType, $language, $ignorePoorContent);

            default:
                throw new Exception("Unknown model type '$this->placeType'.");
        }
    }

    // Encoded Place String - "placeType|placeID|placeString" format string that we use as a temporary format when we need it as a string.

    private static function makeEncodedPlaceString($placeType, $placeID, $placeString)
    {
        return "$placeType|$placeID|" . urlencode($placeString);
    }

    private static function decodeEncodedPlaceString($string)
    {
        if ($string == '') {
            $placeType = '';
            $placeID = 0;
            $placeString = '';
        } else {
            $exploded = explode('|', urldecode($string));
            if (count($exploded) != 3) {
                throw new Exception("Invalid format for '$string'.");
            } else {
                list($placeType, $placeID, $placeString) = $exploded;
            }
        }

        return compact('placeType', 'placeID', 'placeString');
    }

    public function getEncodedPlaceString()
    {
        return self::makeEncodedPlaceString($this->placeType, $this->placeID, $this->placeString);
    }

    public function setTypeAndIdFromEncodedPlaceString($string): void
    {
        $decoded = self::decodeEncodedPlaceString($string);
        foreach ($decoded as $key => $value) {
            $this->$key = $value;
        }
    }

    public function placeSpecificityRank()
    {
        if ($this->placeType == '') {
            return 0;
        }

        return array_search($this->placeType, self::$placeTypes) + 1;
    }

    /* Relationships */

    public function getPlaceObject()
    {
        switch ($this->placeType) {
            case 'ContinentInfo':
                return ContinentInfo::findByName($this->placeString);

            case 'Region':
                // For region, we actually just return a the region's CountryInfo since we don't have a Region class
                return CountryInfo::find($this->placeID);

            case 'CountryInfo':
            case 'CityInfo':
            case 'Listing':
                return $this->placeObjectRelationship;
        }
    }

    public function getPlaceObjectOrFail()
    {
        $object = $this->getPlaceObject();
        if (! $object) {
            throw new Exception("Place object not found for '$this->placeType' '$this->placeID' '$this->placeString'.");
        }

        return $object;
    }

    // Returns the Eloquent relationship (only works with some place types, better to use getPlaceObject() instead)
    public function placeObjectRelationship()
    {
        return $this->belongsTo('App\\Models\\' . $this->placeType, 'placeID');
    }

    /* Scopes */

    public function scopeSamePlaceAs($query, $objectWithPlace)
    {
        return $query->where('placeType', $objectWithPlace->placeType)
            ->where('placeID', $objectWithPlace->placeID)
            ->where('placeString', $objectWithPlace->placeString);
    }

    public function scopeHavePlaceDefined($query)
    {
        return $query->where('placeType', '!=', '');
    }

    public function scopeByEncodedPlaceString($query, $string)
    {
        $decoded = self::decodeEncodedPlaceString($string);
        foreach ($decoded as $key => $value) {
            $query->where($key, $value);
        }

        return $query;
    }

    public function scopeFindByPlaceMatchingCityInfo($query, $cityInfo, $alsoMatchNoPlaceType = false)
    {
        $query->where(function ($query) use ($cityInfo, $alsoMatchNoPlaceType): void {
            $query->whereRaw('false'); // just to make it so we can use orWhere() for whichever one comes first
            $countryInfo = $cityInfo->countryInfo;

            if ($countryInfo && in_array('ContinentInfo', self::$placeTypes)) {
                $query->orWhere(function ($query) use ($countryInfo): void {
                    $query->where('placeType', 'ContinentInfo')->where('placeString', $countryInfo->continent);
                });
            }

            if ($countryInfo && in_array('CountryInfo', self::$placeTypes)) {
                $query->orWhere(function ($query) use ($countryInfo): void {
                    $query->where('placeType', 'CountryInfo')->where('placeID', $countryInfo->id);
                });
            }

            if ($countryInfo && in_array('Region', self::$placeTypes)) {
                $query->orWhere(function ($query) use ($countryInfo, $cityInfo): void {
                    $query->where('placeType', 'Region')->where('placeID', $countryInfo->id)->where('placeString', $cityInfo->region);
                });
            }

            if (in_array('CityInfo', self::$placeTypes)) {
                $query->orWhere(function ($query) use ($cityInfo): void {
                    $query->where('placeType', 'CityInfo')->where('placeID', $cityInfo->id);
                });
            }

            if ($alsoMatchNoPlaceType) {
                $query->orWhere('placeType', '');
            }
        });

        return $query;
    }
}
