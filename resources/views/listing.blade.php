<?php

use App\Models\Listing\Listing;

Lib\HttpAsset::requireAsset('wishlistMain.js');
Lib\HttpAsset::requireAsset('listingDisplay.js');
Lib\HttpAsset::requireAsset('booking-main.js');
Lib\HttpAsset::requireAsset('pannellum');
Lib\HttpAsset::requireAsset('fancybox');

//Hostel Name + City + Country

/* @var Listing $listing */
$nameAndLocation = "$listing->name, ";

//  todo: temp, for fix 'Trying to get property of non-object'
$cityInfo = $cityInfo ?? $listing->cityInfo;

if ($cityInfo) {
    $cityData = $cityInfo->translation();

    $nameAndLocation .= $cityData->city . ', ';
    $nameAndLocation .= $listing->country === 'USA' ? $cityData->region : $cityData->country;

    //City Name Only
    $city = $cityData->city;
    //Country Name Only
    $country = $listing->country === 'USA' ? $cityData->region : $cityData->country;
} else {
    $cityData = new \App\Models\EmptyCityInfo();
    $country = '';
}

//Hostel Name Only
$hostelName = $listing->name;
?>

@extends('layouts/default', ['returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true])

@section('title', !isset($title) ? langGet('SeoInfo.ListingMetaTitle', ['hostelName' => $listing->name, 'city' =>
    $cityData->city, 'country' => $country, 'year' => date('Y')]) : $title)

@section('header')
    <meta name="description" content="{!! langGet('SeoInfo.ListingMetaDescription', [
        'hostelName' => $listing->name,
        'city' => $cityData->city,
        'country' => $country,
        'year' => date('Y'),
    ]) !!}">
    <meta property="og:title" content="{!! langGet('SeoInfo.ListingMetaTitle', [
        'hostelName' => $listing->name,
        'city' => $cityData->city,
        'country' => $country,
        'year' => date('Y'),
    ]) !!}" />
    <meta property="og:description" content="{!! langGet('SeoInfo.ListingMetaDescription', [
        'hostelName' => $listing->name,
        'city' => $cityData->city,
        'country' => $country,
        'year' => date('Y'),
    ]) !!}" />

    @parent

    @isset($schema)
        {!! $schema !!}
    @endisset
@stop

@section('bodyAttributes')
    class="hostel-page"
@stop

@section('content')

    <x-listing.pics-top-listing :listing="$listing" />

    @include('listing.listingScrollNavigation')

    <section class="container mt-5 mt-lg-6">
        <div class="row">
            <div class="col-12 col-lg-8">
                @include('listing.listingBreadcrumb')

                @include('listing.listingContentHead')

                @include('city.banner')

                @if (!in_array('isClosed', $listingViewOptions))
                    @include('bookings._searchFormListing', ['pageType' => 'listing'])
                @endif

                @include('listing.listingSearchResult')
            </div>

            <div class="col-12 col-lg-4">
                @include('listing.listingSearchFormSidebar')
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8">

                @include('listing.listingMainRatings')

                @include('listing.listingMainReviews')

                @include('listing.listingDescription')

                @include('listing.listingVideo')

                @include('listing.listingLocation&Contact')
            </div>

            <div class="col-12 col-lg-4">

                @include('listing.listingSidebar')

                {{-- Flexible part of right sidebar - can be CTA ?? ad space --}}
                <div class="sticky-top mb-6 js-show-if-not-login sticky-top-mobile" style="top: 50px;">
                    @include('articles.sidebar/signupsidebar')
                </div>

            </div>
        </div>
    </section>

    <x-listing.award-section :$listing />

    {{-- from view 'listing.listingSlider' --}}
    <div id="listingMoreHostels">
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>

    <section class="pt-5 pt-sm-6 pb-5 container">
        <div class="col-12 offset-lg-2 col-lg-8">
            @include('listing.listingSubmitRating')
        </div>
    </section>

    @isset($cityInfo->totalListingCount)
        <section class="pt-5 pt-sm-6 pb-5 bg-gray-100">
            <div class="col-12 offset-lg-2 col-lg-8">
                @include('listing.listingMain')
            </div>
        </section>
    @endisset
@stop

@section('pageBottom')

    @parent

    @include('listing.listingPageBottom')

@stop
