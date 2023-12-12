<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Faq extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function subjectable(): MorphTo
    {
        return $this->morphTo();
    }
}
