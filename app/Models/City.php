<?php

namespace App\Models;

use Laravel\Scout\Searchable;

class City extends CityInfo
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'city' => $this->city,
            'country' => $this->country,
            'hostelCount' => $this->hostelCount,
            'totalListingCount' => $this->totalListingCount,
        ];
    }

    public function shouldBeSearchable()
    {
        return $this->isLive();
    }

    public function searchableAs()
    {
        return 'city';
    }
}
