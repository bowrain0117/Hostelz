<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class AltName extends Model
{
    use Searchable;

    protected $table = 'altNames';

    public function toSearchableArray()
    {
        return [
            'id' => $this->getKey(),
            'language' => $this->language,
            'altName' => $this->altName,
            'isShortName' => $this->isShortName,
            'isPreferredName' => $this->isPreferredName,
        ];
    }
}
