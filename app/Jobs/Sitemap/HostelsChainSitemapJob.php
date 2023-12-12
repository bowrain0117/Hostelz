<?php

namespace App\Jobs\Sitemap;

use App\Models\HostelsChain;
use Illuminate\Support\Collection;

class HostelsChainSitemapJob extends SitemapBaseJob
{
    public const KEY = 'hostelsChains';

    protected function getLinks(): Collection
    {
        app('url')->forceScheme('https');

        $langCodes = $this->getLanguages();

        return HostelsChain::isActive()
            ->get()
            ->reduce(function ($carry, $item) use ($langCodes): Collection {
                foreach ($langCodes as $languageCode) {
                    $carry->push($item->getUrl('absolute', $languageCode));
                }

                return $carry;
            }, collect());
    }
}
