<?php

namespace Lib;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Geocoding
{
    protected $table = 'geocodeCache';

    public static $cacheTable = 'geocodeCache'; // just here so we can get the table name without needing an instance of the object

    public static $ACCURACY_TYPE = [
        'unable' => -100, 'approxArea' => -40, 'approxStreet' => -30, 'interpolated' => -20, 'rooftop' => -10, 'latLongLookup' => 0,
    ];

    const GEOCODING_PRECISION = 6;

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'monthly':
                $output .= "\nDelete old data.\n";
                DB::table(self::$cacheTable)->where('dateAdded', '<', Carbon::now()->subYears(5)->format('Y-m-d'))->delete();
                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function geocode($streetAddress, $city, $region, $country)
    {
        $PREFERRED_ACCURACY = self::$ACCURACY_TYPE['rooftop'];

        if ($city === '' || $country === '') {
            logWarning("Can't geocode without the city and country.");

            return false;
        }

        $addressString = ($streetAddress !== '' ? "$streetAddress, " : '') . $city . ($region !== '' ? ", $region" : '') . ", $country";

        // * Check Cache *
        $cached = DB::table(self::$cacheTable)->where('addressString', $addressString)->first();
        if ($cached) {
            return (array) $cached;
        }

        // * Geocode *

        $bestResult = false;

        // Google
        $result = self::googleGeocode($streetAddress, $city, $region, $country);
        if ($result) {
            $bestResult = $result;
        }

        /* can try other geocoders here like this:
        if (!$result || $result['accuracy']<$PREFERRED_ACCURACY)
            [...try other geocoder here...]
         f($result && (!$bestResult || $result['accuracy']>$bestResult['accuracy'])) $bestResult = $result;
        */

        // * Save to Cache *

        // note that "false" results (temporary or unexpected error) aren't saved in the cache
        // (but $ACCURACY_TYPE "unable" ones are, which means the geocoder found no results)

        if (isset($bestResult['latitude']) && ! isset($bestResult['longitude'])) { // apparently this was happening?
            logError("Latitude set but longitude not for '$streetAddress, $city, $region, $country'.");
        }
        if (! isset($bestResult['latitude']) && isset($bestResult['longitude'])) { // just checking
            logError("Latitude not set but longitude is for '$streetAddress, $city, $region, $country'.");
        }

        if ($bestResult) {
            $bestResult['addressString'] = $addressString;
            $bestResult['dateAdded'] = date('Y-m-d');
            $bestResult['latitude'] = (isset($bestResult['latitude']) ? round($bestResult['latitude'], self::GEOCODING_PRECISION) : 0);
            $bestResult['longitude'] = (isset($bestResult['longitude']) ? round($bestResult['longitude'], self::GEOCODING_PRECISION) : 0);

            self::saveResultToCache($bestResult);
        }

        return $bestResult;
    }

    public static function reverseGeocode($latitude, $longitude, $precision)
    {
        debugOutput("reverseGeocode($latitude, $longitude)");

        if ($latitude == 0.0 && $longitude == 0.0) {
            return false;
        }

        $latitude = round($latitude, $precision);
        $longitude = round($longitude, $precision);

        // * Check Cache *
        if ($precision == self::GEOCODING_PRECISION) {
            $cached = DB::table(self::$cacheTable)->where('latitude', $latitude)->where('longitude', $longitude)->first();
        } else {
            $cached = DB::table(self::$cacheTable)
                ->whereRaw("ROUND(latitude, $precision) = ?", [$latitude])->whereRaw("ROUND(longitude, $precision) = ?", [$longitude])->first();
        }
        if ($cached) {
            debugOutput('reverseGeocode cached.');

            return (array) $cached;
        }

        // * Geocode *

        // Google
        $bestResult = self::googleReverseGeocode($latitude, $longitude);
        /* can try other geocoders here like this: if (!$bestResult) [...try other geocoder...] */

        // note that "false" results (temporary or unexpected error) aren't saved in the cache
        // (but $ACCURACY_TYPE "unable" ones are, which means the geocoder found no results)
        if ($bestResult) {
            $bestResult = [
                'addressString' => $bestResult['addressString'] ?? '',
                'accuracy' => $bestResult['accuracy'],
                'dateAdded' => date('Y-m-d'),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $bestResult['country'] ?? '',
                'region' => $bestResult['region'] ?? '',
                'area' => $bestResult['area'] ?? '',
                'area2' => $bestResult['area2'] ?? '',
                'colloquialArea' => $bestResult['colloquialArea'] ?? '',
                'city' => $bestResult['city'] ?? '',
                'cityArea' => $bestResult['cityArea'] ?? '',
                'neighborhood' => $bestResult['neighborhood'] ?? '',
                'streetName' => $bestResult['streetName'] ?? '',
                'source' => $bestResult['source'] ?? '',
            ];

            self::saveResultToCache($bestResult);
        } else {
            debugOutput('No result from googleReverseGeocode.');
        }

        return $bestResult;
    }

    public static function accuracyName($accuracyCode)
    {
        return array_search($accuracyCode, self::$ACCURACY_TYPE);
    }

    private static function saveResultToCache($result)
    {
        debugOutput('Saving to cache.');
        DB::table(self::$cacheTable)->insert($result);
    }

    private static function googleGeocode($streetAddress, $city, $region, $country)
    {
        // Geocoding API V3 - http://code.google.com/apis/maps/documentation/geocoding/
        $result = self::googleGeocodeDoRequest('https://maps.googleapis.com/maps/api/geocode/json?' .
            'address=' . urlencode(($streetAddress !== '' ? "$streetAddress, " : '') . $city . ($region !== '' ? ", $region" : '') . ", $country") .
            '&key=' . urlencode(config('custom.googleApiKey.serverSide')), false);
        if (! $result && $region !== '') {
            return self::googleGeocode($streetAddress, $city, '', $country);
        } // try it without the region

        return $result;
    }

    private static function googleReverseGeocode($latitude, $longitude)
    {
        // Geocoding API V3 - http://code.google.com/apis/maps/documentation/geocoding/
        return self::googleGeocodeDoRequest("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude" .
            '&key=' . urlencode(config('custom.googleApiKey.serverSide')), true);
    }

    // Note:  2,500 requests per 24 hour period. 5 requests per second.
    // Returns false if error.
    private static function googleGeocodeDoRequest($requestURL, $reverseGeocoding)
    {
        minDelayBetweenCalls('googleGeocode', 250); // in miliseconds
        debugOutput("googleGeocodeDoRequest($requestURL, $reverseGeocoding)");

        $return = ['source' => 'Google'];

        $data = self::getContent($requestURL);

        if (! $data) {
            logError("Can't json_decode Google geocoding for '$requestURL'.");

            return false;
        }

        switch ($data['status']) {
            case 'OK':
                break; // just continue
            case 'ZERO_RESULTS':
            case 'INVALID_REQUEST': // happens for some invalid addresses
                $return['accuracy'] = self::$ACCURACY_TYPE['unable'];

                return $return;
            case 'OVER_QUERY_LIMIT':
                logError('Over Google API query limit.', [], 'critical');

                return false;
            default:
                logError("Google geocoding error status '$data[status]' for '$requestURL'.");

                return false;
        }

        $result = $data['results'][0];
        if ($result === false) {
            logError("Missing results for Google geocoding '$requestURL'.");

            return false;
        }

        if ($reverseGeocoding) {
            $return['accuracy'] = self::$ACCURACY_TYPE['latLongLookup'];
        } else {
            switch ($result['geometry']['location_type']) {
                case 'ROOFTOP':
                    $return['accuracy'] = self::$ACCURACY_TYPE['rooftop'];
                    break;
                case 'RANGE_INTERPOLATED':
                    $return['accuracy'] = self::$ACCURACY_TYPE['interpolated'];
                    break;
                case 'GEOMETRIC_CENTER':
                    $return['accuracy'] = self::$ACCURACY_TYPE['approxStreet'];
                    break;
                case 'APPROXIMATE':
                    $return['accuracy'] = self::$ACCURACY_TYPE['approxArea'];
                    break;
                default:
                    logWarning("Google geocoding unknown location_type '" . $result['geometry']['location_type'] . "' for '$requestURL'.");

                    return false;
            }

            $return['latitude'] = $result['geometry']['location']['lat'];
            $return['longitude'] = $result['geometry']['location']['lng'];
            if ($return['latitude'] == 0.0 && $return['longitude'] == 0.0) {
                logWarning("Google geocoding no lat/long returned for '$requestURL'.");

                return false;
            }
        }

        if (! $result['address_components'] || ! is_array($result['address_components'])) {
            logWarning("Missing address_components for '$requestURL'.");

            return false;
        }

        foreach ($result['address_components'] as $component) {
            if (in_array('country', $component['types'])) {
                $return['country'] = $component['long_name'];
            } elseif (in_array('administrative_area_level_1', $component['types'])) {
                $return['region'] = $component['long_name'];
            } elseif (in_array('administrative_area_level_2', $component['types'])) {
                $return['area'] = $component['long_name'];
            } elseif (in_array('administrative_area_level_3', $component['types'])) {
                $return['area2'] = $component['long_name'];
            } elseif (in_array('colloquial_area', $component['types'])) {
                $return['colloquialArea'] = $component['long_name'];
            } elseif (in_array('locality', $component['types'])) {
                $return['city'] = $component['long_name'];
            } elseif (in_array('sublocality', $component['types'])) {
                $return['cityArea'] = $component['long_name'];
            } elseif (in_array('neighborhood', $component['types'])) {
                $return['neighborhood'] = $component['long_name'];
            } elseif (in_array('route', $component['types'])) {
                $return['streetName'] = $component['long_name'];
            }
        }

        $return['addressString'] = $result['formatted_address'];

        return $return;
    }

    public static function getContent($requestURL)
    {
        try {
            $data = Http::retry(3, 100)
                        ->get($requestURL)
                        ->throw()
                        ->json();
        } catch (\Throwable $e) {
            logError($e->getMessage());

            return false;
        }

        return $data;
    }
}
