<?php

namespace App\Jobs\Sitemap;

use App\Http\Controllers\MiscController;
use Illuminate\Support\Collection;

class StaticPagesSitemapJob extends SitemapBaseJob
{
    public const KEY = 'staticPages';

    protected function getLinks(): Collection
    {
        $langCodes = $this->getLanguages();
        app('url')->forceScheme('https');

        return $this->getRouteNames()
            ->map(
                fn ($routeName) => $langCodes->map(
                    fn ($lang) => routeURL($routeName, [], 'absolute', $lang)
                )
            )
            ->flatten();
    }

    protected function getRouteNames(): Collection
    {
        $routeNames = collect([
            'home', 'hostelChain:index', 'allContinents',
        ]);

        MiscController::staticPagesOptions()
            ->reduce(function ($carry, $item): Collection {
                if (in_array($item['alias'], ['privacy-policy', 'termsConditions', 'onHold'])) {
                    return $carry;
                }

                $carry->push($item['alias']);

                return $carry;
            }, $routeNames);

        return $routeNames;
    }
}
