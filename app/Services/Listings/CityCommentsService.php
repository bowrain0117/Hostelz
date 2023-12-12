<?php

namespace App\Services\Listings;

use App\Models\CityInfo;
use App\Models\Languages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CityCommentsService
{
    public function getCityComments(CityInfo $cityInfo): ?Collection
    {
        $cityComments = $cityInfo->cityComments()->areLive()->where('language', Languages::currentCode())->orderBy('id', 'DESC')->get();

        if ($cityComments->isEmpty()) {
            $cityComments = null;
        }

        return $cityComments;
    }

    public function getCitiesComments($cityIDsForCitiesWithHostels): Collection
    {
        $cityComments = CityInfo::whereIn('cityInfo.id', $cityIDsForCitiesWithHostels)
            ->join('cityComments', function ($join): void {
                $join->on('cityInfo.id', '=', 'cityComments.cityID')
                    ->where('cityComments.status', '=', 'approved')
                    ->where('cityComments.language', '=', Languages::currentCode())
                    ->where('cityComments.comment', 'LIKE', DB::raw('CONCAT("%",cityInfo.city,"%")'));
            })
            ->groupBy('cityInfo.city')
            ->orderBy(DB::raw('rand()'))
            ->select('cityComments.*')
            ->get();

        return $cityComments->pluck('comment')->take(15);
    }
}
