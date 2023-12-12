<?php

namespace App\Models;

use Lib\BaseModel;

class Redirect extends BaseModel
{
    public function setEncodedUrlAttribute($value): void
    {
        $this->attributes['encoded_url'] = rawurlencode($this->attributes['old_url']);
    }
}
