<?php

namespace Lib;

use Exception;

/*

Misc useful functions for working with geocoding and geography.

*/

class GeoMath
{
    public static function kmToMiles($km, $round = false)
    {
        $miles = $km * 0.621371;

        return $round ? round($miles, $miles < 1 ? 1 : 0) : $miles;
    }

    public static function milesToKm($miles, $round = false)
    {
        $km = $miles / 0.621371;

        return $round ? round($km, $km < 1 ? 1 : 0) : $km;
    }
}
