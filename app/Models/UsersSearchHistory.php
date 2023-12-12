<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersSearchHistory extends Model
{
    protected $fillable = ['category', 'query', 'itemID'];

    public const MAX_SEARCHES = 5;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
