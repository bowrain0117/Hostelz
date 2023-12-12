<?php

use Illuminate\Support\Js;

Lib\HttpAsset::requireAsset('wishlistMain.js');
Lib\HttpAsset::requireAsset('city.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true  ])

@section('title', $metaValues['title'])

@section('header')
	<meta property="og:title" content="{{ $metaValues['title'] }}"/>
	<meta name="description" content="{{ $metaValues['description'] }}">
	<meta property="og:description" content="{{ $metaValues['description'] }}"/>

	@parent
@stop

@section('bodyAttributes')
	class = "city-page"
@stop

@section('content')
	<section class="city-hero py-5 py-lg-6 mb-3 mb-lg-5">

		@if($heroImage = $cityInfo->heroImage)
			<div class="city-hero-img dark-overlay-before dark-overlay"
			     style="background: #000000 url('{{ $heroImage }}') no-repeat center; background-size: cover;"></div>
		@else
			<div class="city-hero-img dark-overlay-before" style="background: #004369;"></div>
		@endif

		<div class="container position-relative">
			<ul class="breadcrumb" vocab="http://schema.org/" typeof="BreadcrumbList">
				@breadcrumb(langGet('global.Home'), routeURL('home'))

				@if($cityInfo->continent)
					@breadcrumb($cityInfo->continent, $cityInfo->getContinentURL(), 'hidden-xs')
				@endif

				@breadcrumb($cityInfo->country, $cityInfo->getCountryURL())

				@breadcrumb($cityInfo->city, $cityInfo->getURL())

				@breadcrumb($category['name'])
			</ul>

			<div class="title-section">
				<h1 class="title-1 text-left text-white mb-2 mb-lg-3">{{ $metaValues['headerTitle'] }}</h1>
			</div>
		</div>

	</section>

	@include('city.banner')

	@if(is_null($listingsData))
		@include('bookings/_search', [ 'pageType' => 'city' ])
	@else
		<section class="container mb-5 mb-lg-6">
			<div id="listingsSearchResult">
				@include('city.listingsList', $listingsData)
			</div>
		</section>
	@endif

	{{--   from view: listings.listingsRowSlider    --}}
	@if($cityInfo->hostelCount > 0)
		<section id="loadExploreSection">
			<div class="d-flex justify-content-center">
				<div class="spinner-border text-primary" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</section>
	@endif

	@if($category['description'])
		<section class="bg-white py-5 py-lg-6">
			<div class="container">
				<div class="row">
					<div class="col-12 col-lg-8 pr-lg-6">

						{!! $category['description'] !!}

					</div>

					<div class="col-12 col-lg-4">

						<x-slp.categories-sidebar :$cityInfo :$cityCategories />

					</div>
				</div>
			</div>
		</section>
	@endif
@stop

@push('beforeScripts')
	<script>
        document.addEventListener("hostelz:getCityPageSettingsCookie", (event) => {
            event.detail.listingFilters = {{ Js::from($cookiesListingFilters) }};
        })
	</script>
@endpush

@include(
    'city.cityBottom',
    [
        'cityInfo' => $cityInfo,
        'pageType' => "category:{$category['key']}",
        'orderBy' => null,
        'editUrl' => ['target' => 'categoryPage', 'id' => $cityInfo->id, 'categoryType' => $category['key']],
    ]
)


