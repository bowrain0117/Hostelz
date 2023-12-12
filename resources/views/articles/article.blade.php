<?php
Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('headerJsonSchema')
    <script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "Article",
	  "mainEntityOfPage": {
		"@type": "WebPage",
		"@id": "{!! $article->url() !!}"
	},
	  "headline": "{{ clearTextForSchema($article->getArticleTitle()) }}",
	  "description": "{{ clearTextForSchema($article->getSnippet(300)) }}",
	  "image": {
		"@type": "ImageObject",
		"url": "{!! $article->thumbnailUrl('originals') !!}",
		"width": 350,
		"height": 223
	  },
	  "author": {
		"@type": "Person",
		"name": "{{ clearTextForSchema($article->authorName) }}"
	  },
	  "publisher": {
		"@type": "Organization",
		"name": "Hostelz",
		"logo": {
		  "@type": "ImageObject",
		  "url": "https://www.hostelz.com/images/logo-hostelz.png",
		  "width": 180,
		  "height": 40
		}
	  },
	  "datePublished": "{!! $article->updateDate !!}",
	  "dateModified": "{!! $article->updateDate !!}"
	}







    </script>
    {!! $schemaAuthor->toScript() !!}
@stop

@section('title', !empty($article->metaTitle) ? $article->getMetaTitleForDisplay() : $article->getArticleTitle().' - Hostelz.com')

@section('header')

    <link rel="alternate" type="application/rss+xml" title="Hostelz.com {{{ langGet('articles.Articles') }}}"
          href="{!! routeURL('articles', 'rss') !!}"/>
    <meta property="og:title" content="{{{ $article->getMetaTitleForDisplay() }}}"/>
    @if( !empty($article->metaDescription))
        <meta name="description" content="{{{ $article->getMetaDescriptionForDisplay() }}}">
        <meta property="og:description" content="{{{ $article->getMetaDescriptionForDisplay() }}}"/>
    @endif

    {!! $articleText['headerInsert'] !!}

@stop

@section('content')

    <section class="hero text-white dark-overlay bg-cover hero-blog flex-center mb-3 mb-lg-5">
        <div class="dark-overlay hero-blog flex-center w-100">
            <img src="@if ($article->thumbnailUrl()){!! $article->thumbnailUrl('originals') !!}@else{!! routeURL('images', 'hostel-blog-backpacking.jpg') !!}@endif"
                 alt="{{{ $article->getArticleTitle() }}}" class="bg-image">
            <div class="card-img-overlay d-flex align-items-center">
                <div class="w-100 overlay-content container flex-center--column">
                    <ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0 text-white" vocab="http://schema.org/"
                        typeof="BreadcrumbList">
                        {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                        {!! breadcrumb(langGet('articles.Articles'), routeURL('articles')) !!}
                        @if( $article->category )
                            {!! breadcrumb($article->category->breadcrumb, $article->category->url) !!}
                        @endif
                        {!! breadcrumb($articleTitle ?? $article->getArticleTitle()) !!}
                    </ul>

                    <h1 class="text-center mb-0 h2 text-white">{{ $articleTitle ?? $article->getArticleTitle() }}</h1>

                    @if ($article->isForLogedInContent() )
                        <div class="row mb-3">
                            <div class="col">
                                <span class="bg-primary rounded text-white px-2 font-weight-bold">@langGet('global.Pluz')</span>
                            </div>
                        </div>
                    @endif

                    <p class="py-2 mb-1 text-center article-author-info">
                        <span class="d-inline-flex align-middle mr-2">
                            <img src="
                                    @if ($article->user->profilePhoto)
                                        {!! $article->user->profilePhoto->url([ 'thumbnails' ]) !!}
                                    @else
                                        {!! routeURL('images', 'hostelz-blogger-writer.jpg') !!}
                                   @endif
                                    " alt="" class="avatar avatar-lg p-1"
                            >
                            @if($article->user->isAdmin())
                                <span style="margin-left: -15px; z-index: 1; position: relative;">
                                    @include('partials.svg-icon', ['svg_id' => 'verified-user-hostelz-white', 'svg_w' => '24', 'svg_h' => '24'])
                                </span>
                            @endif
                        </span>
                        Written by
                        <x-user-page-link :user="$article->user"/>

                        @if ($article->publishDate != '' || $article->updateDate != '')
                            <span class="mx-1">|</span>

                            @if ($article->updateDate)
                                Last update: {!! carbonGenericFormat($article->updateDate) !!}
                            @elseif ($article->publishDate)
                                {!! carbonGenericFormat($article->publishDate) !!}
                            @endif
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </section>

    <article class="container">
        <div class="row flex-column flex-lg-row">
            <div class="col-12 col-lg-8 pb-5 mb-5">


                @if($article->isForLogedInContent())
                    <div class="article mb-3 pt-2" id="articleText">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="article mb-3 pt-2">
                        {!! $article->getArticleTextForDisplay()['text'] !!}
                    </div>
                @endif

                <x-author-box :user="$article->user"/>

            </div>

            {{--sidebar--}}

            <div class="col-12 col-lg-4 pb-5 mb-5">
                <div class="pl-xl-4 mb-6">
                    @include('articles.sidebar/video')
                </div>

                <div class="pl-xl-4 sticky-top mb-6 js-show-if-not-login sticky-top-mobile" style="top: 50px;">
                    @include('articles.sidebar/signupsidebar')
                </div>
            </div>

        </div>
    </article>

    @if($article->id === 229)
        <section class="mb-3 mb-lg-5">
            <div class="container">
                @section('headerJsonSchema')
                    <script type="application/ld+json">
                        {"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Does Hostelworld offer discounts?","acceptedAnswer":{"@type":"Answer","text":"Yes, Hostelworld runs special promotions. In this article we share the latest official promo codes.\n"}},{"@type":"Question","name":"Does Hostelworld offer Student Discounts?","acceptedAnswer":{"@type":"Answer","text":"No, Hostelworld does not offer discounts specifically for students."}},{"@type":"Question","name":"Is Hostelworld legit?","acceptedAnswer":{"@type":"Answer","text":"Yes, Hostelworld is legit. It is one of the most popular websites to make reservations for hostels and budget accommodation."}}]}






                    </script>
                @stop
            </div>
        </section>
    @endif

    @if(!empty($articles) && $articles->isNotEmpty())
        <section class="mb-7 mb-sm-7">
            <div class="container">
                <div class="row mb-4 align-items-center">
                    <div class="col-sm-8 mb-2 mb-sm-0">
                        <h3>@langGet('articles.otherArticles')</h3>
                    </div>
                    <div class="col-sm-4 d-sm-flex">
                        <a href="{!! routeURL('articles') !!}" title="All Articles"
                           class="btn btn-muted ml-auto mb-2 tt-n font-weight-600">All Articles</a>
                    </div>
                </div>

                <div class="row mb-5">
                    @foreach ($articles as $articleLink)
                        @if ($articleLink->id != $article->id)
                            @include('articles.card', ['item' => $articleLink])
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@stop

@section('pageBottom')
    @parent

    <script>
        var articleOptions = JSON.parse('{!! json_encode( [
            "edit" => [
                'link' => routeURL('staff-articles', [$article->id ]),
                'text' => 'edit article'
            ],
            'article' => [
                'getTextURL' => routeURL( Route::currentRouteName() === 'staff-articles' ? 'getArticleTextAdmin' : 'getArticleText', [$article->id ]),
            ]
        ], JSON_HEX_APOS, JSON_HEX_QUOT) !!}');
    </script>

    <script>
        initializeTopHeaderSearch();

        $(document).ready(function () {
            var articleText = $('#articleText');

            if (articleText.length > 0) {
                loadArticleText();
            }


            function loadArticleText() {
                $.get(articleOptions.article.getTextURL)
                    .done(function (result) {
                        if (result) {
                            $('#articleText').html(result)
                        }
                    })
                    .fail(function (e) {
                        console.log("error");
                    })
            }
        });
    </script>
@stop
