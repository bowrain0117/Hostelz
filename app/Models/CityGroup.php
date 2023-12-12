<?php

namespace App\Models;

use Laravel\Scout\Searchable;

class CityGroup extends CityInfo
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'cityGroup' => $this->cityGroup,
        ];
    }

    public function shouldBeSearchable()
    {
        return $this->isLive();
    }

    public function searchableAs()
    {
        return 'cityGroup';
    }
}
