<?php

namespace App\Jobs\Sitemap;

use App\Models\CityInfo;
use App\Services\ListingCategoryPageService;
use Illuminate\Support\Collection;

class ListingsCategoryPagesSitemapJob extends SitemapBaseJob
{
    public const KEY = 'categoryPage';

    private ListingCategoryPageService $service;

    public function __construct()
    {
        $this->service = resolve(ListingCategoryPageService::class);
    }

    protected function getLinks(): Collection
    {
        app('url')->forceScheme('https');

        return CityInfo::select(['id', 'city', 'country', 'region'])
            ->areLive()
            ->cursor()
            ->map($this->categoryUrlForCity(...))
            ->filter()
            ->flatten()
            ->collect();
    }

    protected function categoryUrlForCity($cityInfo)
    {
        return $this->service->activeForCity($cityInfo)
            ->map(fn ($item) => $item->category->url($cityInfo))
            ->toArray();
    }
}
