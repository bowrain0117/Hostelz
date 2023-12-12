<?php

namespace App\Models;

use Laravel\Scout\Searchable;

class Country extends CityInfo
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'country' => $this->country,
            'hostelCount' => $this->hostelCount,
            'totalListingCount' => $this->totalListingCount,
        ];
    }

    public function shouldBeSearchable()
    {
        return $this->isLive();
    }

    public function makeAllSearchableUsing($query)
    {
        return $query->with('countryInfo.geonames');
    }

    public function searchableAs()
    {
        return 'country';
    }
}
