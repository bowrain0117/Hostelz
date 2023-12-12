<?php

namespace App\Jobs\Sitemap;

use App\Models\Article;
use Illuminate\Support\Collection;

class ArticlesSitemapJob extends SitemapBaseJob
{
    public const KEY = 'articles';

    protected function getLinks(): Collection
    {
        $langCodes = $this->getLanguages();

        app('url')->forceScheme('https');

        $articlesUrls = Article::where('status', 'published')
            ->whereIn('placementType', Article::$placementOptions)
            ->get()
            ->map(fn ($article) => $langCodes->map(fn ($lang) => $article->getUrl('absolute', $lang)))
            ->flatten();

        $categoriesUrls = collect(Article::categoriesData())
            ->map(fn ($category) => $category->url)
            ->flatten();

        return $articlesUrls->merge($categoriesUrls);
    }
}
