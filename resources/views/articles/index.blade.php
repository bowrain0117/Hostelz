<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.BlogMetaTitle', ['year' => date("Y")] ))

@section('header')
	<link rel="alternate" type="application/rss+xml" title="Hostelz.com {{{ langGet('articles.Articles') }}}" href="{!! routeURL('articles', 'rss') !!}" />
	<meta name="description" content="{!! langGet('SeoInfo.BlogMetaDescription', ['year' => date("Y")]) !!}">
	<meta property="og:title" content="{!! langGet('SeoInfo.BlogMetaTitle', ['year' => date("Y")] ) !!}" />
    <meta property="og:description" content="{!! langGet('SeoInfo.BlogMetaDescription', ['year' => date("Y")]) !!}" />
@stop

@section('content')
	<section class="hero text-white dark-overlay bg-cover hero-blog flex-center mb-3 mb-lg-5">
		<div class="dark-overlay hero-blog flex-center w-100">
			<img src="{!! routeURL('images', 'hostel-blog-backpacking.jpg') !!}" alt="@langGet('articles.h1')" class="bg-image">
			<div class="card-img-overlay d-flex align-items-center">
				<div class="w-100 overlay-content container flex-center--column">
					<ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0 text-white" vocab="http://schema.org/" typeof="BreadcrumbList">
						{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
						{!! breadcrumb(langGet('articles.Articles')) !!}
					</ul>
					<h1 class="text-center mb-0 text-white">@langGet('articles.h1') <a href="{!! routeURL('articles', 'rss') !!}" target="_blank" title="RSS Hostel Blog" class="text-hover-primary"><i class="fas fa-rss-square text-sm"></i></a></h1>
				</div>
			</div>
		</div>
	</section>

	<div class="container">
		<p>@langGet('articles.welcome')</p>
	</div>

	@if($categories)
		<section class="py-4 py-sm-5">
			<div class="container">
				<h2 class="mb-5 text-center">Pick your Category</h2>
				<div class="row">

					@foreach ($categories as $category)
						<div class="d-flex align-items-lg-stretch mb-4 col-lg-4">
							<div class="card border-0 text-white dark-overlay shadow-lg hover-animate w-100">
								<img alt="{!! strip_tags($category->titleIndex) !!}" class="card-img" src="{!! routeURL('images', "articles/{$category->slug}-thmb.jpeg") !!}"><a href="{{ $category->url }}" class="tile-link"></a>

								<div class="card-img-overlay d-flex align-items-center">
									<div class="w-100 overlay-content">
										<h2 class="text-shadow text-uppercase mb-0 text-center text-white h3">{!! $category->titleIndex !!}</h2>
									</div>
								</div>
						  	</div>
						</div>
					@endforeach

				</div>
			</div>
		</section>
	@endif

	@foreach ($articlesByCategories as $categoryArticles)
		@if($categoryArticles->articles->isNotEmpty())
			<section class="my-4 my-sm-5">
				<div class="container">
					<div class="row mb-4 align-items-center">
						<div class="col-sm-8 mb-2 mb-sm-0">
							<h3>{!! $categoryArticles->title !!}</h3>
						</div>
						<div class="col-sm-4 d-sm-flex">
							<a href="{!! $categoryArticles->url !!}" title="All Articles" class="btn btn-muted ml-auto mb-2 tt-n font-weight-600">All Articles</a>
						</div>
					</div>

					<div class="row mb-5">
						@foreach ($categoryArticles->articles as $article)
							@include('articles.card', ['item' => $article])
						@endforeach
					</div>
				</div>
			</section>
		@endif
	@endforeach
@stop


@section('pageBottom')
	@parent

	<script>
		initializeTopHeaderSearch();
	</script>
@stop
