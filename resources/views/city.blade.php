<?php
Lib\HttpAsset::requireAsset('wishlistMain.js');
Lib\HttpAsset::requireAsset('city.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true  ])

@include('city.cityHead')

@section('content')

    @include('city.cityHero')

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

    <x-district.neighborhood :cityName="$cityInfo->city" :items="$districts"/>

    <section class="bg-white py-5 py-lg-6">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 pr-lg-6">

                    @include('city.cityInformation')

                    <x-city::seo-table :items="$seoTable" :cityName="$cityInfo->city"/>

                    <x-city.faq :cityInfo="$cityInfo" :priceAVG="$priceAVG"/>

                </div>

                <div class="col-12 col-lg-4">

                    @include('ads.ad_city')

                    <x-slp.categories-sidebar :$cityInfo :$cityCategories />

                    @include('city.cityNearByCities')

                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @include('city.cityTipsSuggestions')
                </div>
            </div>
        </div>
    </section>
@stop

@include(
    'city.cityBottom',
    [
        'cityInfo' => $cityInfo,
        'pageType' => 'city',
        'orderBy' => null,
        'editUrl' => ['target' => 'city', 'id' => $cityInfo->id],
    ]
)
