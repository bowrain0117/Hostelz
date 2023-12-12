<?php

namespace App\Services;

use App\Exceptions\LocationException;
use Stevebauman\Location\Facades\Location;

class LocationService
{
    /**
     * @throws LocationException
     */
    public function getCityByIP(?string $ip = null): string
    {
        return $this->getLocationData($ip)->cityName;
    }

    /**
     * @throws LocationException
     */
    public function getRegionByIP(?string $ip = null): string
    {
        return $this->getLocationData($ip)->regionName;
    }

    /**
     * @throws LocationException
     */
    public function getCountryByIP(?string $ip = null): string
    {
        return $this->getLocationData($ip)->countryName;
    }

    /**
     * @throws LocationException
     */
    public function getLatitudeByIP(?string $ip = null): string
    {
        return $this->getLocationData($ip)->latitude;
    }

    /**
     * @throws LocationException
     */
    public function getLongitudeByIP(?string $ip = null): string
    {
        return $this->getLocationData($ip)->longitude;
    }

    /**
     * @throws LocationException
     */
    private function getLocationData(?string $ip = null)
    {
        $locationData = Location::get($ip);

        if (! $locationData) {
            throw new LocationException("Can't get location data for this ip: $ip");
        }

        return $locationData;
    }
}
