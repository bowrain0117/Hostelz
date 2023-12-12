<?xml version = "1.0" encoding = "utf-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>

        <title>@langGet('articles.Articles') - Hostelz.com'</title>
        <description>@langGet('articles.ArticlesDesc')</description>
        <link>{!! routeURL('articles', [], 'absolute') !!}</link>
        <atom:link href="{!! routeURL('articles', ['rss'], 'absolute') !!}" rel="self" type="application/rss+xml"/>

        @foreach ($articles as $article)
            <item>
                <title>{!! htmlspecialchars($article->getArticleTitle(), ENT_COMPAT) !!}</title>
                <link>{!! routeURL('articles', $article->placement, 'absolute') !!}</link>
                <guid>{!! routeURL('articles', $article->placement, 'absolute') !!}</guid>
                @if($article->updateDate || $article->publishDate)
                <pubDate>
                    {!! $article->updateDate ? carbonFromDateString($article->updateDate)->format('r') : carbonFromDateString($article->publishDate)->format('r') !!}
                </pubDate>
                @endif
                <description>{!! html_entity_decode($article->getSnippet(300), ENT_COMPAT|ENT_SUBSTITUTE) !!}</description>
            </item>
        @endforeach

    </channel>
</rss>
