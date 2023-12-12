<?php

namespace App\Console\Commands;

use App\Jobs\Sitemap\ArticlesSitemapJob;
use App\Jobs\Sitemap\CitiesSitemapJob;
use App\Jobs\Sitemap\ContinentsSitemapJob;
use App\Jobs\Sitemap\DistrictsSitemapJob;
use App\Jobs\Sitemap\HostelsChainSitemapJob;
use App\Jobs\Sitemap\ListingsCategoryPagesSitemapJob;
use App\Jobs\Sitemap\ListingsSitemapJob;
use App\Jobs\Sitemap\SlpSitemapJob;
use App\Jobs\Sitemap\StaticPagesSitemapJob;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Sitemap\SitemapIndex;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the sitemap.';

    public function handle(): void
    {
        info('GenerateSitemap v.0.5 #####');

        $sitemapFilesName = collect()
            ->merge(StaticPagesSitemapJob::getKeysAndDispatch())
            ->merge(ContinentsSitemapJob::getKeysAndDispatch())
            ->merge(CitiesSitemapJob::getKeysAndDispatch())
            ->merge(ListingsCategoryPagesSitemapJob::getKeysAndDispatch())
            ->merge(HostelsChainSitemapJob::getKeysAndDispatch())
            ->merge(ListingsSitemapJob::getKeysAndDispatch())
            ->merge(SlpSitemapJob::getKeysAndDispatch())
            ->merge(DistrictsSitemapJob::getKeysAndDispatch())
            ->merge(ArticlesSitemapJob::getKeysAndDispatch());

        $this->createIndexSitemap($sitemapFilesName);

        info(self::class . ' end ================');
    }

    private function createIndexSitemap(Collection $sitemapFilesName): void
    {
        app('url')->forceScheme('https');

        $sitemapIndex = SitemapIndex::create();
        $sitemapFilesName->each(function ($item) use ($sitemapIndex): void {
            $sitemapIndex->add(route('home') . "/sitemap_{$item}.xml");
        });
        $sitemapIndex->writeToFile(public_path('sitemap_index.xml'));
    }
}
