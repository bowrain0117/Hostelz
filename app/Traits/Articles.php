<?php

namespace App\Traits;

use App\Models\Article;
use App\Services\ArticlesServices;
use Lib\PageCache;

trait Articles
{
    public function articles(ArticlesServices $services, $slug = '')
    {
        PageCache::addCacheTags('articles');

        //  for /articles
        if ($slug === '') {
            $articlesByCategories = $services->articlesByCategories();
            $categories = $services->articlesCategories();
            $ogThumbnail = url('images', 'hostel-blog-backpacking.jpg');

            return view('articles.index', compact('articlesByCategories', 'categories', 'ogThumbnail'));
        }

        if ($activeCategory = $services->isCategoryPage($slug)) {
            return view('articles.list', $services->getCategoryData($activeCategory));
        }

        //  for /articles/rss
        if ($slug === 'rss') {
            return response(view('articles.rss')->with('articles', $services->getIndexRSSArticlesData()), 200)
                ->header('Content-Type', 'text/xml; charset=utf-8');
        }

        // Get Article
        $articleData = $services->getArticleData($slug);
        if (! $articleData) {
            logWarning("Can't find live article '$slug'.");
            abort(404);
        }

        return view('articles.article', $articleData);
    }

    public function getArticleText(ArticlesServices $services, Article $article)
    {
        return $services->getArticleText($article);
    }

    public function getArticleCategoryText(ArticlesServices $services, $slug): string
    {
        return $services->getArticleCategoryText($slug);
    }

    public function articlePrivatePreview($articleID, $verificationCode)
    {
        $article = Article::findOrFail($articleID);
        if ($verificationCode !== $article->privatePreviewPassword()) {
            return accessDenied();
        }

        return view('articles.privateArticle', compact('article'))->with('articleText', $article->getArticleTextForDisplay());
    }
}
