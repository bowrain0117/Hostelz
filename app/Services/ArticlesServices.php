<?php

namespace App\Services;

use App\Models\Article;
use App\Schemas\AuthorSchema;

class ArticlesServices
{
    private $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function getLiveArticlesQuery()
    {
        return $this->article::where('status', 'published');
    }

    public function getIndexRSSArticlesData()
    {
        return $this->article
            ->published()
            ->where('placementType', 'articles')
            ->orderBy('updateDate', 'DESC')
            ->with('pics')
            ->get();
    }

    public function getArticleData($slug): ?array
    {
        $liveArticleQuery = $this->getLiveArticlesQuery();

        $article = with(clone $liveArticleQuery)
            ->whereIn('placementType', ['articles', 'unlisted article', 'hostel owners tips', 'exclusive content'])
            ->where('placement', $slug)
            ->first();

        if (! $article) {
            return null;
        }

        $articles = with(clone $liveArticleQuery)
            ->where([
                ['placementType', $article->placementType],
                ['id', '!=', $article->id],
            ])
            ->limit(3)
            ->orderBy('updateDate', 'DESC')
            ->with('pics')
            ->get();

        $articleText = $article->getArticleTextForDisplay();
        $articleTitle = $article->getArticleTitle();
        $ogThumbnail = $article->pics->first()?->url(['originals'], 'absolute');
        $schemaAuthor = AuthorSchema::for($article->user)->getSchema();

        return compact('article', 'articles', 'articleText', 'articleTitle', 'ogThumbnail', 'schemaAuthor');
    }

    public function isCategoryPage($slug)
    {
        return collect($this->article::categoriesData())->filter(function ($value, $key) use ($slug) {
            return $value->slug === $slug && $value->showOnIndex;
        })->first();
    }

    public function getCategoryData($category): array
    {
        $articles = $this->article::where('status', 'published')
            ->where('placementType', $category->placementType)
            ->orderBy('updateDate', 'DESC')
            ->with('pics')
            ->get();
        $ogThumbnail = url('images', "articles/{$category->slug}.jpg");

        return compact('articles', 'category', 'ogThumbnail');
    }

    public function articlesByCategories(): array
    {
        $activeCategories = collect($this->article::categoriesData())->filter(function ($value, $key) {
            return $value->showOnIndex;
        });

        $articles = $this->article;

        return $activeCategories->each(function ($item, $key) use ($articles) {
            $item->articles = $articles::where('status', 'published')
                ->where('placementType', $item->placementType)
                ->limit(3)
                ->orderBy('updateDate', 'DESC')
                ->with('pics')
                ->get();

            return $item;
        })->all();
    }

    public function articlesCategories(): array
    {
        return $this->article::categoriesData();
    }

    public function getArticleText($article)
    {
        if (! $article->isForLogedInContent()) {
            return $article->getArticleTextForDisplay()['text'];
        }

        if (! auth()->check()) {
            return view('articles.articleNotLoginText')->render();
        }

        return $article->getArticleTextForDisplay()['text'];
    }

    public function getArticleCategoryText($slug): string
    {
        $category = $this->article::getCategoryBySlug($slug);

        if ($this->article::isShowCategoryForNotLogedIn($slug)) {
            return view('articles.categoryText', compact('category'))->render();
        }

        if (! auth()->check()) {
            return view('articles.categoryTextNotLogin', compact('category'))->render();
        }

        return view('articles.categoryText', compact('category'))->render();
    }
}
