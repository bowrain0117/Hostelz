<?php

namespace App\Jobs\Sitemap;

use App\Enums\CategorySlp;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class SlpSitemapJob extends SitemapBaseJob
{
    public const KEY = 'slp';

    protected function getLinks(): Collection
    {
        app('url')->forceScheme('https');

        $slpLinks = SpecialLandingPage::published()->get()->map->path;

        $categoriesLinks = CategorySlp::values()
            ->filter(fn ($item) => Route::has($this->getRouteName($item)))
            ->map(fn ($item) => routeURL($this->getRouteName($item)));

        return $slpLinks->merge($categoriesLinks);
    }

    private function getRouteName($category)
    {
        return 'slp.index.' . $category;
    }
}
