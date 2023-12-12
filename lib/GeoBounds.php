<?php

namespace Lib;

use Exception;

class GeoBounds
{
    public $swPoint /* min point */;

    public $nePoint /* max point */;

    public function __construct($swLatitude = null, $swLongitude = null, $neLatitude = null, $neLongitude = null)
    {
        $this->swPoint = new GeoPoint($swLatitude, $swLongitude);
        $this->nePoint = new GeoPoint($neLatitude, $neLongitude);
    }

    // * Static Methods *

    public static function makeFromApproximateDistanceFromPoint(GeoPoint $point, $distance, $kmOrMiles = 'miles')
    {
        $bounds = new self;

        // From: http://www.movable-type.co.uk/scripts/latlong-db.html
        // (we use the approximate version)

        $earthRadius = ($kmOrMiles == 'miles' ? 3959 : 6371); // earth's radius

        $bounds->nePoint->longitude = $point->longitude + rad2deg($distance / $earthRadius / cos(deg2rad($point->latitude)));
        $bounds->swPoint->longitude = $point->longitude - rad2deg($distance / $earthRadius / cos(deg2rad($point->latitude)));
        $bounds->swPoint->latitude = $point->latitude - rad2deg($distance / $earthRadius);
        $bounds->nePoint->latitude = $point->latitude + rad2deg($distance / $earthRadius);
        if ($bounds->nePoint->longitude > 180.0) {
            $bounds->nePoint->longitude -= 360; // wrap around if it overlaps 180 degrees
            list($bounds->swPoint->longitude, $bounds->nePoint->longitude) = [$bounds->nePoint->longitude, $bounds->swPoint->longitude]; // Swap min/max
        }
        if ($bounds->swPoint->longitude < -180.0) {
            $bounds->swPoint->longitude += 360; // wrap around if it overlaps 180 degrees
            list($bounds->swPoint->longitude, $bounds->nePoint->longitude) = [$bounds->nePoint->longitude, $bounds->swPoint->longitude]; // Swap min/max
        }
        if ($bounds->nePoint->latitude > 90.0) {
            $bounds->nePoint->longitude = 90.0;
        } // limit it to the north pole
        if ($bounds->swPoint->latitude < -90.0) {
            $bounds->swPoint->longitude = -90.0;
        } // limit it to the south pole

        return $bounds;
    }

    // $array - Array or collection of GeoPoints or an array or object with 'latitude' and 'longitude' defined.

    public static function makeFromPoints($array, $ignoreZeroPoints = true)
    {
        $bounds = new self;

        foreach ($array as $item) {
            $point = new GeoPoint($item);
            if (! $point->isValid()) {
                throw new Exception('Invalid point for ' . json_encode($item));
            }
            if ($ignoreZeroPoints && $point->isZeroZero()) {
                continue;
            }
            $bounds->extendToPoint($point);
        }

        return $bounds;
    }

    // * Other Methods *

    public function isValid()
    {
        if (! $this->swPoint->isValid() || ! $this->nePoint->isValid()) {
            return false;
        }
        if ($this->swPoint->latitude > $this->nePoint->latitude) {
            return false;
        }

        /* Note:  swPoint->longitude <= nePoint->longitude may not be true
        because the bounding box can cross the prime meridian. */

        return true;
    }

    public function crossesPrimeMeridian()
    {
        return $this->swPoint->longitude > $this->nePoint->longitude;
    }

    public function extendToPoint(GeoPoint $point)
    {
        if ($this->swPoint->latitude === null || $point->latitude < $this->swPoint->latitude) {
            $this->swPoint->latitude = $point->latitude;
        }
        if ($this->nePoint->latitude === null || $point->latitude > $this->nePoint->latitude) {
            $this->nePoint->latitude = $point->latitude;
        }
        if ($this->swPoint->longitude === null || $point->longitude < $this->swPoint->longitude) {
            $this->swPoint->longitude = $point->longitude;
        }
        if ($this->nePoint->longitude === null || $point->longitude > $this->nePoint->longitude) {
            $this->nePoint->longitude = $point->longitude;
        }

        if (! $this->isValid()) {
            throw new Exception('Invalid boundingBox.');
        }
    }

    public function unionWithBounds(self $otherBounds)
    {
        $this->swPoint->latitude = min($this->swPoint->latitude, $otherBounds->swPoint->latitude);
        $this->nePoint->latitude = max($this->nePoint->latitude, $otherBounds->nePoint->latitude);

        $this->nePoint->longitude = max($this->nePoint->longitude, $otherBounds->nePoint->longitude);

        if ($this->crossesPrimeMeridian()) {
            if ($otherBounds->crossesPrimeMeridian()) {
                $this->swPoint->longitude = min($this->swPoint->longitude, $otherBounds->swPoint->longitude);
            }
        // (otherwise we keep the current sw longitude)
        } else {
            if ($otherBounds->crossesPrimeMeridian()) {
                $this->swPoint->longitude = min($this->swPoint->longitude, $otherBounds->swPoint->longitude);
            } else {
                $this->swPoint->longitude = $otherBounds->swPoint->longitude;
            }
        }
    }

    public function expandToMinimumRadius($distance, $kmOrMiles = 'miles')
    {
        $this->unionWithBounds(self::makeFromApproximateDistanceFromPoint($this->centerPoint(), $distance, $kmOrMiles));

        return $this;
    }

    public function centerPoint()
    {
        return $this->swPoint->midwayToPoint($this->nePoint);
    }

    public function containsPoint(GeoPoint $point)
    {
        if (! $this->isValid()) {
            throw new Exception('Invalid boundingBox.');
        }

        if ($point->latitude < $this->swPoint->latitude || $point->latitude > $this->nePoint->latitude) {
            return false;
        }

        if ($this->crossesPrimeMeridian()) {
            if ($point->longitude < $this->swPoint->longitude && $point->longitude > $this->nePoint->longitude) {
                return false;
            }
        } else {
            if ($point->longitude < $this->swPoint->longitude || $point->longitude > $this->nePoint->longitude) {
                return false;
            }
        }

        return true;
    }

    public function query($query)
    {
        if (! $this->isValid()) {
            throw new Exception('Invalid boundingBox.');
        }

        $query->where('latitude', '>=', $this->swPoint->latitude)->where('latitude', '<=', $this->nePoint->latitude);

        if ($this->crossesPrimeMeridian()) {
            $query->where(function ($query) {
                $query->where('longitude', '>=', $this->swPoint->longitude)->orWhere('longitude', '<=', $this->nePoint->longitude);
            });
        } else {
            $query->where('longitude', '>=', $this->swPoint->longitude)->where('longitude', '<=', $this->nePoint->longitude);
        }

        return $query;
    }

    public function asArray($roundToDecimal = null)
    {
        return ['swPoint' => $this->swPoint->asArray($roundToDecimal), 'nePoint' => $this->nePoint->asArray($roundToDecimal)];
    }

    public function json($roundToDecimal = null)
    {
        return json_encode($this->asArray($roundToDecimal));
    }
}
