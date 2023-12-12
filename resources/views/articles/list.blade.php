<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', !empty($category->metaTitle) ? htmlentities($category->metaTitle) : htmlentities($category->title.' - Hostelz.com'))

@section('header')
	<link rel="alternate" type="application/rss+xml" title="Hostelz.com {{{ langGet('articles.Articles') }}}" href="{!! routeURL('articles', 'rss') !!}" />
	<meta property="og:title" content="{{{ !empty($category->metaTitle) ? htmlentities($category->metaTitle) : htmlentities($category->title.' - Hostelz.com') }}}" />

	@if( !empty($category->metaDescription))
		<meta name="description" content="{{{ $category->metaDescription }}}">
		<meta property="og:description" content="{{{ $category->metaDescription }}}" />
	@endif
@stop

@section('content')
<section class="hero text-white dark-overlay bg-cover hero-blog flex-center mb-3 mb-lg-5">
	<div class="hero text-white dark-overlay bg-cover hero-blog flex-center w-100">
		<img src="{!! routeURL('images', "articles/{$category->slug}.jpg") !!}" alt="{{ $category->title }}" title="{{ $category->title }}" class="bg-image">
		<div class="card-img-overlay d-flex align-items-center"> 
			<div class="w-100 overlay-content container flex-center--column">
				<ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0 text-white" vocab="http://schema.org/" typeof="BreadcrumbList">
    				{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
    				{!! breadcrumb(langGet('articles.Articles'), routeURL('articles')) !!}
    				{!! breadcrumb($category->breadcrumb) !!}
    			</ul>
				<h1 class="text-center mb-0 text-white">{!! $category->title !!} <a href="{!! routeURL('articles', 'rss') !!}" target="_blank" title="RSS Hostel Blog" class="text-hover-primary"><i class="fas fa-rss-square text-sm"></i></a></h1>
			</div>
		</div>
	</div>
</section>

<div class="container mt-2 mt-lg-2" id="categoryText">
	<div class="d-flex justify-content-center">
		<div class="spinner-border text-primary" role="status">
			<span class="sr-only">Loading...</span>
		</div>
	</div>
</div>

@if($articles->isNotEmpty())
	<!--  Feature Articles  -->
	<section class="py-4 py-sm-5">
		<div class="container">
			<div class="mb-4">
				<h3>@langGet('articles.LatestPosts')</h3>
			</div>
			<div class="row mb-5">

				@foreach ($articles as $article)

					<script type="application/ld+json">
					{
					  "@context": "https://schema.org",
					  "@type": "Blog",
					  "mainEntityOfPage": {
						"@type": "WebPage",
						"@id": "https://www.hostelz.com/articles"
					  },
					  "headline": "{{ clearTextForSchema($article->getArticleTitle()) }}", 
					  "description": "{{ clearTextForSchema($article->getSnippet(300)) }}",
					  "image": {
						"@type": "ImageObject",
						"url": "{!! $article->thumbnailUrl('originals') !!}",
						"width": 1000,
						"height": 459
					  },
					  "author": {
						"@type": "Organization",
						"name": "{!! clearTextForSchema($article->authorName) !!}"
					  },
					  "publisher": {
						"@type": "Organization",
						"name": "Hostelz",
						"logo": {
						  "@type": "ImageObject",
						  "url": "https://www.hostelz.com/images/logo-hostelz.png",
						  "width": 180,
						  "height": 42
						}
					  }
					}
					</script>

					@include('articles.card', ['item' => $article])
 
				@endforeach

			</div>
		</div>
	</section>
@else
	<section class="py-4 py-sm-5">
		<div class="container">
			<div class="mb-4">
				<h3>Sorry, no article is live yet. We are working on it! Sign up to get notified as soon as our travel guides are live!</h3>
			</div>
		</div>
	</section>
@endif


@stop


@section('pageBottom')
	@parent

	<script>
		var articleCategoryOptions = JSON.parse('{!! json_encode( [
            "getCategoryTextURL" => routeURL('getArticleCategoryText', [ $category->slug ], 'absolute')
        ], JSON_HEX_APOS, JSON_HEX_QUOT) !!}');
	</script>

	<script>
		initializeTopHeaderSearch();
		
		$(document).ready(function () {

			loadArticleCategoryText();

			function loadArticleCategoryText() {
				$.get( articleCategoryOptions.getCategoryTextURL )
					.done(function(result) {
						if (result) {
							$('#categoryText').html(result)
						}
					})
					.fail(function(e) {
						console.log( "error" );
					})
			}
		});
	</script>
@stop

