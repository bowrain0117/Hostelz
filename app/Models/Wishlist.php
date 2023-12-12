<?php

namespace App\Models;

use App\Models\Listing\Listing;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'wishlists';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function listings()
    {
        return $this->belongsToMany(Listing::class)->withTimestamps();
    }

    public function scopeOfUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function getListingsCountAttribute()
    {
        return $this->listings->count();
    }

    public function getPathAttribute()
    {
        return routeURL('wishlist:show', $this->id, 'absolute');
    }

    public function getImageAttribute()
    {
        return $this->listings->isNotEmpty() ? $this->listings->first()->thumbnailURL() : '';
    }
}
