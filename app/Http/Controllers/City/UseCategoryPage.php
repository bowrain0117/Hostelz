<?php

namespace app\Http\Controllers\City;

use App\Enums\Listing\CategoryPage;
use App\Models\CityInfo;
use App\Services\ListingCategoryPageService;
use App\Services\Listings\CityInfoService;
use App\Services\Listings\CityListingsService;
use App\Services\Listings\Filters\ListingsFiltersService;
use App\Services\Listings\ListingsOptionsService;
use Lib\PageCache;

/**
 * @property CityInfoService $cityInfoService
 * @property CityListingsService $cityListingsService
 * @property ListingsFiltersService $listingsFiltersService
 */
trait UseCategoryPage
{
    public function getCategoryPageData($country, $city, $categoryName, $doBookingSearch): null|string|array
    {
        $category = CategoryPage::tryFrom($categoryName);
        if (is_null($category)) {
            return null;
        }

        /** @var CityInfo $cityInfo */
        $cityInfo = $this->cityInfoService->getCityInfo($country, $city);

        $listingsShowOptions = ListingsOptionsService::getDefaultListingsShowOptions(
            [
                'listingFilters' => [
                    'propertyType' => ['Hostel'],
                    'suitableFor' => [$category->suitableFor()],
                ],
            ]
        );

        $listingsData = ! $doBookingSearch
            ? $this->cityListingsService->getListingsData(
                $cityInfo,
                $listingsShowOptions,
            )
            : null;

        if ($listingsData && $listingsData['hostelCount'] < CategoryPage::MIN_LISTINGS_COUNT) {
            return ['cityRedirectUrl' => $cityInfo->path];
        }

        $this->listingsFiltersService->setListings($cityInfo);
        $listingFilters = $this->listingsFiltersService->getListingsFilters();

        $metaValues = $this->cityInfoService->getMetaValues(
            $cityInfo,
            [
                'headerTitle' => $category->title($cityInfo->city),
                'title' => $category->metaTitle($cityInfo->city),
                'description' => $category->metaDescription($cityInfo->city),
            ]
        );

        PageCache::addCacheTags(['city:' . $cityInfo->id, $category::TABLE_KEY]);

        return [
            'data' => [
                'category' => [
                    'name' => $category->fullName(),
                    'key' => $category->value,
                    'description' => $cityInfo->getCategoryPageDescription($category),
                ],
                'cityInfo' => $cityInfo,
                'metaValues' => $metaValues,
                'listingFilters' => $listingFilters,
                'listingsData' => $listingsData,
                'cookiesListingFilters' => $listingsShowOptions['listingFilters'],
                'cityCategories' => resolve(ListingCategoryPageService::class)
                    ->activeExceptCurrentForCity($category, $cityInfo),
            ],
        ];
    }
}
