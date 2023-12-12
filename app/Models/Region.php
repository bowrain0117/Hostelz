<?php

namespace App\Models;

use Laravel\Scout\Searchable;

class Region extends CityInfo
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'region' => $this->region,
            'displaysRegion' => $this->displaysRegion,
        ];
    }

    public function shouldBeSearchable()
    {
        return $this->isLive();
    }

    public function searchableAs()
    {
        return 'region';
    }
}
