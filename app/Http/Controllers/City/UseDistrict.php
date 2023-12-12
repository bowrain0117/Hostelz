<?php

namespace app\Http\Controllers\City;

use App\Models\District;
use App\Services\Listings\CityInfoService;
use App\Services\Listings\CityListingsService;
use App\Services\Listings\Filters\ListingsFiltersService;
use App\Services\Listings\ListingsOptionsService;

/**
 * @property CityInfoService $cityInfoService
 * @property CityListingsService $cityListingsService
 * @property ListingsFiltersService $listingsFiltersService
 */
trait UseDistrict
{
    private function findDistrict($country, $city, $districtSlug): ?District
    {
        return District::byFullLocation($country, $city, $districtSlug)->first();
    }

    private function handleDistrictRequest(District $district, bool $doBookingSearch = false)
    {
        $this->listingsFiltersService->setListings($district->city);
        $listingFilters = $this->listingsFiltersService->getListingsFilters();

        $metaValues = $this->cityInfoService->getMetaValues($district->city);
        $metaValues['districtTitle'] = $district->title;
        $metaValues['title'] = langGet(
            'SeoInfo.DistrictMetaTitle',
            [
                'title' => $district->title,
                'year' => date('Y'),
                'minPrice' => $metaValues['lowestDormPrice'],
            ]
        );

        $metaValues['description'] = langGet(
            'SeoInfo.DistrictMetaDescription',
            [
                'districtName' => $district->name,
                'cityName' => $district->city->city,
            ]
        );

        $orderBy = [
            'type' => 'district',
            'value' => $district->id,
            'title' => $district->name,
        ];

        $listingsData = ! $doBookingSearch
            ? $this->cityListingsService->getListingsData(
                $district->city,
                ListingsOptionsService::getDefaultListingsShowOptions([
                    'resultsOptions' => [
                        'orderBy' => $orderBy,
                    ],
                ])
            )
            : null;

        return view(
            'districts.show',
            [
                'district' => $district,
                'cityInfo' => $district->city,
                'metaValues' => $metaValues,
                'listingFilters' => $listingFilters,
                'orderBy' => $orderBy,
                'pageType' => 'district',
                'listingsData' => $listingsData,
            ]
        );
    }
}
