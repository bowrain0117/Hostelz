<?php

namespace App\Services;

use App\Models\CityInfo;
use App\Models\Pic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PicsService
{
    public function getCityPics(CityInfo $cityInfo): ?Collection
    {
        $cityPics = $cityInfo->pics()->where('status', 'ok')->orderBy('isPrimary', 'desc')->orderBy('id', 'desc')->get();

        if ($cityPics->isEmpty()) {
            $cityPics = null;
        }

        return $cityPics;
    }

    public function getContinentPics(Collection $continentsCountries): Collection
    {
        $continentPics = collect();
        foreach ($continentsCountries as $continent => $continentCountries) {
            $pic = $this->getContinentFeaturedPhoto($continent);

            if (is_null($pic)) {
                foreach ($continentCountries->shuffle() as $country) {
                    $cities = $country->cityInfos()->liveCities()->pluck('id');

                    $pic = $this->getContinentCityPic($cities);

                    if (! is_null($pic)) {
                        break;
                    }
                }
            }

            $continentPics[$continent] = $pic;
        }

        return $continentPics;
    }

    private function getContinentFeaturedPhoto(string $continent): Model|null
    {
        return Pic::query()
            ->join('cityInfo', 'cityInfo.id', '=', 'pics.subjectID')
            ->select('pics.*')
            ->where('cityInfo.continent', $continent)
            ->where('pics.featuredPhoto', true)
            ->inRandomOrder()
            ->limit(1)
            ->first();
    }

    private function getContinentCityPic(Collection $cities): Model|null
    {
        return Pic::query()
            ->where('subjectType', 'cityInfo')
            ->whereIn('subjectID', $cities)
            ->whereIn('type', ['user', 'old'])
            ->inRandomOrder()
            ->limit(1)
            ->first();
    }
}
