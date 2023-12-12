<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    use HasFactory;

    public const SESSION_COMPARE_KEY = 'listingsToCompare';

    public const MAX_COMPARE_ITEMS = 3;

    public const MORE_THAN_MAX_COMPARE_ITEMS = 4;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'listing_id',
    ];
}
