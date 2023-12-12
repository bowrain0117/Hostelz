<?php

namespace App\Services;

use App\Models\CityInfo;
use Illuminate\Support\Collection;

class CityService
{
    public function getBestFor(CityInfo $cityInfo): Collection
    {
        $bestForTypes = ['partying', 'groups', 'socializing', 'quiet', 'female_solo_traveller', 'couples', 'seniors'];

        $ret = collect($bestForTypes)->mapWithKeys(function ($item) use ($cityInfo) {
            return [$item => $cityInfo->getBestHostelByType($item)];
        });

        $ret->prepend($cityInfo->getCheapestHostel(), 'cheapest');
        $ret->prepend($cityInfo->getTopRatedHostel(), 'topRated');

        return $ret->filter();
    }

    public function getViewDataForCitySearch($isCityGroupPage, $pageCityInfo, $countryInfo): array
    {
        if ($isCityGroupPage) {
            return [
                'query' => $pageCityInfo->translation()->cityGroup,
                'itemId' => $countryInfo->id . '-' . $pageCityInfo->cityGroup,
                'url' => $pageCityInfo->getCityGroupURL('absolute'),
            ];
        }

        if ($pageCityInfo->translation()->region !== '') {
            return [
                'query' => $pageCityInfo->translation()->region,
                'itemId' => $countryInfo->id . '-' . $pageCityInfo->region,
                'url' => $pageCityInfo->getRegionURL('absolute', null, true),
            ];
        }

        return [
            'query' => $countryInfo->country,
            'itemId' => $countryInfo->id,
            'url' => $countryInfo->getURL('absolute'),
        ];
    }
}
