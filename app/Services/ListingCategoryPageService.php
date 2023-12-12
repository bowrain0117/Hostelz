<?php

namespace App\Services;

use App\Enums\Listing\CategoryPage;
use App\Models\CityInfo;
use Illuminate\Support\Collection;

class ListingCategoryPageService
{
    public function activeForCity(CityInfo $cityInfo)
    {
        return $this->allForCity($cityInfo)
            ->reject(fn ($item) => $item->listingsCount < CategoryPage::MIN_LISTINGS_COUNT);
    }

    public function activeExceptCurrentForCity(CategoryPage $current, CityInfo $cityInfo)
    {
        return $this->all()
            ->reject(fn (CategoryPage $cat) => $cat === $current)
            ->map(fn (CategoryPage $category) => $this->data($category, $cityInfo))
            ->reject(fn ($item) => $item->listingsCount < CategoryPage::MIN_LISTINGS_COUNT);
    }

    public function allForCity(CityInfo $cityInfo): Collection
    {
        return $this->all()
            ->map(fn (CategoryPage $category) => $this->data($category, $cityInfo));
    }

    public function all(): Collection
    {
        return collect(CategoryPage::cases())
            ->sortBy('name')
            ->values();
    }

    public function data(CategoryPage $category, CityInfo $cityInfo)
    {
        return (object) [
            'listingsCount' => $cityInfo->countListingsCategoryPage($category),
            'category' => $category,
            'cityInfo' => $cityInfo,
        ];
    }
}
