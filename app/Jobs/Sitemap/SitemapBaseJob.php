<?php

namespace App\Jobs\Sitemap;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Spatie\Sitemap\Sitemap;

abstract class SitemapBaseJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Dispatchable;

    public const KEY = '';

    public $timeout = 600;

    public function handle(): void
    {
        $this->generateSitemap(
            $this->getLinks(),
            static::KEY
        );
    }

    public static function getKeysAndDispatch(): Collection
    {
        self::dispatch()->onQueue('sitemap');

        return collect([static::KEY]);
    }

    abstract protected function getLinks();

    public function generateSitemap(Collection $links, string $fileName): void
    {
        app('url')->forceScheme('https');

        Sitemap::create()
            ->add($links)
            ->writeToFile(public_path("sitemap_{$fileName}.xml"));
    }

    protected function getLanguages(): Collection
    {
        return collect([null]);
//        todo: for languages
//        return collect(Languages::allLiveSiteCodes());
    }
}
