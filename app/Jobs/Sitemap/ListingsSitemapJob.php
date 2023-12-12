<?php

namespace App\Jobs\Sitemap;

use App\Models\Listing\Listing;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class ListingsSitemapJob extends SitemapBaseJob
{
    public const KEY = 'listings';

    public const ITEMS_COUNT = 50000;

    public static function getKeysAndDispatch(): Collection
    {
        self::dispatch()->onQueue('sitemap');

        $chunksCount = Listing::select('id')
            ->areLive()
            ->get()
            ->nth(self::ITEMS_COUNT)
            ->count();

        return collect()->range(1, $chunksCount)
            ->map(fn ($item) => self::KEY . '_' . $item);
    }

    public function handle(): void
    {
        Listing::select(['id', 'name', 'mgmtBacklink', 'propertyType'])
            ->areLive()
            ->cursor()
            ->chunk(self::ITEMS_COUNT)
            ->each(function (LazyCollection $chunk, $i) {
                $this->generateSitemap(
                    $chunk->map(fn ($listing) => $listing->path)->collect(),
                    self::KEY . '_' . ($i + 1)
                );
            });
    }

    protected function getLinks()
    {
        // TODO: Implement getLinks() method.
    }
}
