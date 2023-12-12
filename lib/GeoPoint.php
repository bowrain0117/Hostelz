<?php

namespace Lib;

use Exception;

class GeoPoint
{
    public $latitude;

    public $longitude;

    public function __construct($latitudeOrArrayOrObject = null, $longitude = null)
    {
        if ($longitude === null && is_array($latitudeOrArrayOrObject)) {
            $this->latitude = $latitudeOrArrayOrObject['latitude'];
            $this->longitude = $latitudeOrArrayOrObject['longitude'];
        } elseif ($longitude === null && is_object($latitudeOrArrayOrObject)) {
            $this->latitude = $latitudeOrArrayOrObject->latitude;
            $this->longitude = $latitudeOrArrayOrObject->longitude;
        } else {
            $this->latitude = $latitudeOrArrayOrObject;
            $this->longitude = $longitude;
        }
    }

    public function isValid()
    {
        if ($this->latitude < -90 || $this->latitude > 90 || $this->longitude > 180 || $this->longitude < -180) {
            return false;
        }

        return true;
    }

    public function isZeroZero()
    {
        return $this->latitude == 0.0 && $this->longitude == 0.0;
    }

    public function equals(self $otherPoint)
    {
        return $this->latitude == $otherPoint->latitude && $this->longitude == $otherPoint->longitude;
    }

    public function roundToPrecision($decimals)
    {
        $this->latitude = round($this->latitude, $decimals);
        $this->longitude = round($this->longitude, $decimals);

        return $this;
    }

    public function midwayToPoint(self $otherPoint)
    {
        return new self(($this->latitude + $otherPoint->latitude) / 2, ($this->longitude + $otherPoint->longitude) / 2);
    }

    public function distanceToPoint(self $otherPoint, $kmOrMiles = 'km', $roundTo = null)
    {
        // Note: This doesn't produce an exception or warning.  The calling code is expected to check for a null result.
        if (! $this->isValid() || ! $otherPoint->isValid()) {
            return null;
        }

        // From: http://www.movable-type.co.uk/scripts/latlong-db.html
        $earthRadius = ($kmOrMiles == 'miles' ? 3959 : 6371); // earth's radius

        $dLat = deg2rad($otherPoint->latitude - $this->latitude);
        $dLon = deg2rad($otherPoint->longitude - $this->longitude);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($otherPoint->latitude)) *
            sin($dLon / 2) * sin($dLon / 2);

        $result = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $roundTo !== null ? round($result, $roundTo) : $result;

        /*
        alt method:

        http://sgowtham.net/blog/2009/08/04/php-calculating-distance-between-two-locations-given-their-gps-coordinates/
        $dist = sin(deg2rad($row['latitude'])) * sin(deg2rad($near['latitude'])) +
                        cos(deg2rad($row['latitude'])) * cos(deg2rad($near['latitude'])) * cos(deg2rad($row['longitude']-$near['longitude']));
        $dist = rad2deg(acos($dist));
        $miles = $dist * 60 * 1.1515;
        */
    }

    public function isInBounds(GeoBounds $bounds)
    {
        return $bounds->containsPoint($this);
    }

    public function asArray($roundToDecimal = null)
    {
        return [
            'latitude' => $roundToDecimal ? round($this->latitude, $roundToDecimal) : $this->latitude,
            'longitude' => $roundToDecimal ? round($this->longitude, $roundToDecimal) : $this->longitude,
        ];
    }

    public function json($roundToDecimal = null)
    {
        return json_encode($this->asArray($roundToDecimal));
    }

    public function mapLink()
    {
        if (! $this->isValid() || $this->isZeroZero()) {
            return '';
        }

        $point = $this->asArray();

        return "https://maps.google.com/?q={$point['latitude']},{$point['longitude']}";
    }
}
