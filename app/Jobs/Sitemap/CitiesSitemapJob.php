<?php

namespace App\Jobs\Sitemap;

use App\Models\CityInfo;
use Illuminate\Support\Collection;

class CitiesSitemapJob extends SitemapBaseJob
{
    public const KEY = 'cities';

    public const ITEMS_COUNT = 10000;

    public static function getKeysAndDispatch(): Collection
    {
        self::dispatch()->onQueue('sitemap');

        $chunksCount = CityInfo::select('id')
            ->areLive()
            ->get()
            ->nth(self::ITEMS_COUNT)
            ->count();

        return collect()::range(1, $chunksCount)
            ->map(fn ($item) => self::KEY . '_' . $item);
    }

    public function handle(): void
    {
        $langCodes = $this->getLanguages();

        CityInfo::select(['id', 'city', 'country', 'region'])
            ->areLive()
            ->chunk(
                self::ITEMS_COUNT,
                function ($items) use (&$count, $langCodes): void {
                    $fileName = self::KEY . '_' . ++$count;

                    $links = $items->reduce(function ($carry, $item) use ($langCodes): Collection {
                        foreach ($langCodes as $languageCode) {
                            $carry->push($item->getUrl('absolute', $languageCode));
                        }

                        return $carry;
                    }, collect());

                    $this->generateSitemap($links, $fileName);
                }
            );
    }

    protected function getLinks()
    {
        // TODO: Implement getLinks() method.
    }
}
