<?php

namespace App\Models;

use App\Helpers\EventLog;
use App\Models\Listing\Listing;
use Lib\BaseModel;

class HostelsChain extends BaseModel
{
    public const IMAGE_WIDTH = 450;

    public const IMAGE_HEIGHT = 300;

    public const IMG_MEDIUM_WIDTH = 900;

    public const IMG_MEDIUM_HEIGHT = 600;

    /*  attributes  */

    public function getPathAttribute()
    {
        return routeURL('hostelChain:show', $this->slug, 'absolute');
    }

    public function getListingsCountAttribute()
    {
        return $this->listings()->areLive()->count();
    }

    public function getImageCardAttribute()
    {
        return $this->pic ? $this->pic->url(['cards']) : '';
    }

    public function getImageThumbnailsAttribute()
    {
        return $this->pic ? $this->pic->url(['thumbnails']) : '';
    }

    /*  scopes  */

    public function scopeIsActive($query)
    {
        return $query->where('isActive', 1);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /* Relationships */

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    public function pic()
    {
        return $this->hasOne(\App\Models\Pic::class, 'subjectID')
                    ->where('subjectType', 'hostelsChains')
                    ->where('type', 'images');
    }

    /*  other   */

    public static function forOptions()
    {
        return self::select(['id', 'name'])->isActive()->get()->pluck('name', 'id')->toArray();
    }

    public function getUrl($urlType = 'auto', $language = null)
    {
        return routeURL('hostelChain:show', $this->slug, $urlType, $language);
    }

    public function savePic($originalName, $filePath)
    {
        $result = Pic::makeFromFilePath($filePath, [
            'subjectType' => 'hostelsChains', 'subjectID' => $this->id, 'type' => 'images', 'status' => 'new',
        ], self::picFixPicOutputTypes());

        if ($result) {
            EventLog::log('staff-hostelChain', 'update', 'hostelsChainsImage', $this->id, 'setHostelsChainsImage');
        }

        return $result;
    }

    public static function picFixPicOutputTypes()
    {
//        Pic::make100DirsLocal('hostelsChains/images/originals');
//        Pic::make100DirsLocal('hostelsChains/images/thumbnails');
//        Pic::make100DirsLocal('hostelsChains/images/medium');
//        Pic::make100DirsLocal('hostelsChains/images/webp_thumbnails');
//        Pic::make100DirsLocal('hostelsChains/images/webp_medium');
//        Pic::make100DirsLocal('hostelsChains/images/tiny');

        return config('pics.hostelsChainOptions');
    }
}
