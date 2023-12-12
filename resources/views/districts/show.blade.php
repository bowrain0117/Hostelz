<?php
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

                @breadcrumb($district->name)
            </ul>

            <div class="title-section">
                <h1 class="title-1 text-left text-white mb-2 mb-lg-3">{{ $district->title }}</h1>
            </div>
        </div>
    </section>

    <x-district.main :$district :$listingsData/>

@stop

@include(
    'city.cityBottom',
    [
        'cityInfo' => $cityInfo,
        'pageType' => 'district',
        'orderBy' => $orderBy,
        'editUrl' => ['target' => 'district', 'id' => $district->id],
    ]
)
