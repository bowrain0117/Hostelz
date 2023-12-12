<?php

namespace App\Jobs\Sitemap;

use App\Models\District;
use Illuminate\Support\Collection;

class DistrictsSitemapJob extends SitemapBaseJob
{
    public const KEY = 'districts';

    protected function getLinks(): Collection
    {
        app('url')->forceScheme('https');

        return District::active()->get()->map->path;
    }
}
