<?php

use App\Models\Languages;

if ($isCityGroupPage)
    $areaName = $pageCityInfo->translation()->cityGroup;
elseif ($pageCityInfo->translation()->region != '')
    $areaName = $pageCityInfo->translation()->region;
else
    $areaName = $pageCityInfo->translation()->country;

Lib\HttpAsset::requireAsset('cities.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.CitiesMetaTitle', [ 'area' => $areaName, 'year' => date("Y")]))

@section('header')
    <meta name="description"
          content="{!! langGet('SeoInfo.CitiesMetaDescription', ['area' => $areaName, 'year' => date('Y') ]) !!}">
    <meta property="og:title"
          content="{!! langGet('SeoInfo.CitiesMetaTitle', [ 'area' => $areaName, 'year' => date("Y")]) !!}"/>
    <meta property="og:description"
          content="{!! langGet('SeoInfo.CitiesMetaDescription', ['area' => $areaName, 'year' => date('Y') ]) !!}"/>

@stop

@section('bodyAttributes')
    class="countries-page" style=""
@stop

{{--@section('headerNavBottom')
    <div class="smallMenu mb-n2 mb-lg-0">
        <div class="container">
            <ul class="navbar-nav m-auto flex-row d-none d-md-flex justify-content-center">
                <li class="nav-item mx-4 mx-lg-5" id="all-link">
                    <a data-smooth-scroll="" href="#all-cities-section" class="nav-link">@langGet('cities.Submenu1')</a>
                </li>
                <li class="nav-item mx-4 mx-lg-5" id="map-link">
                    <a data-smooth-scroll="" href="#map-section" class="nav-link">@langGet('cities.Submenu2')</a>
                </li>
                <li class="nav-item mx-4 mx-lg-5" id="know-link">
                    <a data-smooth-scroll="" href="#review-section" class="nav-link">@langGet('cities.Submenu3')</a>
                </li>
                <li class="nav-item mx-4 mx-lg-5" id="tips-link">
                    <a data-smooth-scroll="" href="#tips-section" class="nav-link">@langGet('cities.Submenu4')</a>
                </li>
                <li class="nav-item mx-4 mx-lg-5" id="faq-link">
                    <a data-smooth-scroll="" href="#faq-section" class="nav-link">@langGet('cities.Submenu5')</a>
                </li>
            </ul>
        </div>
    </div>
@stop--}}

@section('content')

    {{--Open Screen--}}
    <section class="mb-4 mb-md-5">
        <!--  Breadcrumbs  -->
        <div class="container">
            <ul class="breadcrumb black px-0 mx-sm-n3 mx-lg-0">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb($pageCityInfo->translation()->continent, $pageCityInfo->getContinentURL()) !!}
                @if ($isCityGroupPage || $pageCityInfo->translation()->region != '')
                    {!! breadcrumb($pageCityInfo->translation()->country, $pageCityInfo->getCountryURL()) !!}
                @endif
                {!! breadcrumb($areaName) !!}
            </ul>
            <h1 class="mb-3 mb-lg-4 pb-md-2 h2">
                {!! langGet('city.HostelsInCity', [ 'city' => $areaName, 'cities' => $areaName ]) !!}
                <img src="{!! ($countryInfo->countryCode()) ? routeURL('images', 'flags/' . strtolower($countryInfo->countryCode()) . '.svg') : routeURL('images', 'flags/globe.svg'); !!}"
                     title="{{ langGet('city.HostelsInCity', [ 'city' => $areaName ]) }}"
                     alt="{{ langGet('city.HostelsInCity', [ 'city' => $areaName ]) }}"
                     style="max-width: 50px;"
                     class="ml-4 img-fluid"
                >
            </h1>
            {!! langGet('cities.IntroText', [ 'cities' => $areaName ]) !!}
        </div>
    </section>

    {{--All Cities--}}
    <section class="container mb-3 mb-md-4" id="all-cities-section">
        <div class="row mb-3 mb-md-4">
            <div class="col-md-12">
                <h2>@langGet('cities.AllCities', [ 'cities' => $areaName ])</h2>
                @langGet('cities.AllCitiesText', [ 'cities' => $areaName, 'count' => $citiesInfo->all(), 'count' => count($citiesInfo->all())])
            </div>
        </div>

        <div class="vue-cities-tab">
            <cities-tab
                    :data="{{ $citiesInfo }}"
                    :all="'{{ __('cities.AllTypes') }}'"
                    :hostels="'{{ __('cities.HostelsOnly') }}'">
            </cities-tab>
        </div>
    </section>

    {{--Map--}}
    @if ($mapPoints)
        <section class="container py-5 mt-2" id="map-section">
            <h2 class="mb-3 mb-sm-4">@langGet('cities.AllCitiesMapTitle', [ 'cities' => $areaName ])</h2>
            @langGet('cities.AllCitiesMapText', [ 'cities' => $areaName ])
            <div class="map-wrapper-450 mb-5 mb-sm-6">
                <div id="mapCanvas" class="h-100"></div>
            </div>
        </section>
    @endif

    @if($topCitiesListings->isNotEmpty())
        <section class="container" id="best-hostels-section">
            <div class="vue-listings-row-slider">
                <slider-wrapper :top-cities-listings="{{ $topCitiesListings }}" :city-more="'{{ __('city.more') }}'"/>
            </div>
        </section>
    @endif

    {{--About Hostels--}}
    <section class="bg-gray-100 py-5 mt-2" id="review-section">
        <div class="container">
            <h2 class="mb-3 mb-sm-4">@langGet('cities.GuideBackpackingTitle', [ 'cities' => $areaName ])</h2>

            @php $hasOtherRegions = $regionsList && empty($regionOrCityGroupSlug) @endphp
            <div class="row">
                <div @class([
                        'col-lg-8' => $hasOtherRegions || $cityGroupsList,
                        'col-lg-12' => !($hasOtherRegions || $cityGroupsList)
                        ])>
                    <div class="shadow-1 mb-4 p-3 p-sm-4 bg-white rounded">
                        @if ($description)
                            <p>{!! nl2br(trim($description->data)) !!}</p>
                            @if ($description->user && $description->user->nickname != '')
                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                                    <div class="d-flex align-items-center">
                                        @if ($description->user->profilePhoto)
                                            <img src="{!! $description->user->profilePhoto->url([ 'thumbnails' ]) !!}"
                                                 alt="expert for {{{ $areaName }}} hostels"
                                                 title="expert for {{{ $areaName }}} hostels"
                                                 class="avatar avatar-lg mr-4">
                                        @else
                                            <img src="{!! routeURL('images', 'hostelz-blogger-writer.jpg') !!}"
                                                 alt="local expert for {{{ $pageCityInfo->city }}} hostels"
                                                 title="local expert for {{{ $pageCityInfo->city }}} hostels"
                                                 class="avatar avatar-lg mr-4">
                                        @endif
                                        <p class="mb-0 ml-2 font-montserat"><small
                                                    class="d-block text-gray-600">{!! langGet('cities.LocalExpertTitle', [ 'cities' => $areaName]) !!}</small> {{{ $description->user->nickname }}}
                                        </p>

                                    </div>
                                </div>
                            @endif
                        @else
                            <p>{!! langGet('cities.NoCitiesInformation', [ 'cities' => $areaName ]) !!}</p>
                        @endif
                    </div>
                </div>

                @if ($hasOtherRegions || $cityGroupsList)
                    <div class="col-lg-4">
                        <div class="shadow-1 mb-4 p-3 p-sm-4 d-flex flex-column bg-white rounded">
                            @if ($hasOtherRegions)
                                <h6 class="font-weight-600 mb-lg-4 mb-5">
                                        <?php
                                        $regionType = $countryInfo->regionType;
                                        if (empty($regionType)) $regionType = 'Regions';
                                        ?>
                                    {{ __("CountryInfo.forms.options.regionType.$regionType") }}
                                </h6>
                            @endif

                            <div class="contentBoxContent">
                                @if ($hasOtherRegions)
                                    @foreach ($regionsList as $regionCityInfo)
                                        <div class="flex mb-3">
                                            <img src="{!! routeURL('images', 'pin.svg') !!}"
                                                 title="@langGet("global.HostelsIn") {{{ $regionCityInfo->translation()->region }}}"
                                                 alt="@langGet("global.HostelsIn") {{{ $regionCityInfo->translation()->region }}}"
                                                 class="mr-2">
                                            <a href="{!! $regionCityInfo->getRegionURL() !!}"
                                               class="mr-2 pr-1 font-weight-600"
                                               title="@langGet("global.HostelsIn") {{{ $regionCityInfo->translation()->region }}}">{{{ $regionCityInfo->translation()->region }}}</a>
                                        </div>
                                    @endforeach
                                @endif

                                @if ($cityGroupsList)
                                    <h5 class="bold">{{ __('cities.cityGroups') }}</h5>
                                    <ul class="list-group regionsList">
                                        @foreach ($cityGroupsList as $cityInfo)

                                            <div class="flex mb-3">
                                                <img src="{!! routeURL('images', 'pin.svg') !!}"
                                                     title="{{ __('global.HostelsIn') }} {{ $cityInfo->translation()->cityGroup }}"
                                                     alt="{{ __('global.HostelsIn') }} {{ $cityInfo->translation()->cityGroup }}"
                                                     class="mr-2"
                                                >
                                                <a href="{!! $cityInfo->getCityGroupURL() !!}"
                                                   class="mr-2 pr-1 font-weight-600"
                                                   title="{{ __('global.HostelsIn') }} {{ $cityInfo->translation()->cityGroup }}"
                                                >
                                                    {{ $cityInfo->translation()->cityGroup }}
                                                </a>
                                            </div>

                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>

    {{--Tips & Suggestions--}}
    @if ($cityComments)
        <section class="container py-5 mt-2" id="tips-section">
            <h2 class="mb-3 mb-sm-4">@langGet('city.TravelTipsTitle', [ 'city' => $areaName, 'cities' => $areaName ])</h2>

            <p class="mb-5">@langGet('city.TravelTipsText', [ 'city' => $areaName, 'cities' => $areaName ])</p>

            @include('city.cityComments', ['cityComments' => $cityComments])

        </section>
    @endif

    {{--FAQ--}}

    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "@langGet('cities.FAQArea1Title1', [ 'cities' => $areaName ])",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "@lang('cities.FAQArea1Schema1', ['cities' => $areaName])"
    }
  },{
    "@type": "Question",
    "name": "@langGet('cities.FAQArea1Title2', [ 'cities' => $areaName ])",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "@langGet('cities.FAQArea1Schema2', [ 'cities' => $areaName ])"
    }
  },{
    "@type": "Question",
    "name": "@langGet('cities.FAQArea1Title3', [ 'cities' => $areaName ])",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "@langGet('cities.FAQArea1Schema3', [ 'cities' => $areaName, 'WhenBookLink' => routeURL('articles', 'when-to-book-hostels') ])"
    }
  },{
    "@type": "Question",
    "name": "@langGet('cities.FAQArea2', [ 'cities' => $areaName ])",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "@langGet('cities.FAQArea2Schema1', [ 'cities' => $areaName ])"
    }
  },{
    "@type": "Question",
    "name": "@langGet('cities.FAQArea2Title2', [ 'cities' => $areaName ])",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "@langGet('cities.FAQArea2Schema2', [ 'cities' => $areaName ])"
    }
  }]
}



    </script>

    <section class="container py-5 mt-2" id="faq-section">
        <h2 class="mb-3 mb-sm-4">@langGet('cities.FAQTopTitle', [ 'cities' => $areaName ])</h2>
        <p class="mb-5">@langGet('cities.FAQTopText', [ 'cities' => $areaName ])</p>
        <h3 class="text-primary mb-4">@langGet('cities.FAQArea1', [ 'cities' => $areaName ])</h3>
        <div class="shadow-1 rounded py-4 px-3 px-sm-4 mb-5 position-relative">
            <div class="p-md-2">
                <div class="row">
                    <div class="col-md-7">
                        <h4 class="mb-md-4">@langGet('cities.FAQArea1Title1', [ 'cities' => $areaName ])</h4>
                        @lang('cities.FAQArea1Text1', ['cities' => $areaName])
                    </div>
                    <div class="col-md-5">
                        <h4 class="mb-md-4">@langGet('cities.FAQArea1Title2', [ 'cities' => $areaName ])</h4>
                        @langGet('cities.FAQArea1Text2', [ 'cities' => $areaName ])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-7">
                        <h4 class="mb-md-4 mt-5">@langGet('cities.FAQArea1Title3', [ 'cities' => $areaName ])</h4>
                        @langGet('cities.FAQArea1Text3', [ 'cities' => $areaName, 'WhenBookLink' => routeURL('articles', 'when-to-book-hostels') ])
                    </div>
                    <div class="col-md-5">
                        <h4 class="mb-md-4 mt-5"></h4>
                        <p class=""></p>
                    </div>
                </div>
            </div>
            <img src="{!! routeURL('images', 'faq-icon.svg') !!}" alt="#" class="faq-icon">
        </div>
        <h3 class="text-primary mb-4">@langGet('cities.FAQArea2', [ 'cities' => $areaName ])</h3>
        <div class="shadow-1 rounded py-4 px-3 px-sm-4 mb-5 mb-sm-6 position-relative">
            <div class="p-md-2">
                <div class="row">
                    <div class="col-md-7">
                        <h4 class="mb-md-4">@langGet('cities.FAQArea2Title1', [ 'cities' => $areaName ])</h4>
                        @langGet('cities.FAQArea2Text1', [ 'cities' => $areaName ])
                    </div>
                    <div class="col-md-5">
                        <h4 class="mb-md-4">@langGet('cities.FAQArea2Title2', [ 'cities' => $areaName ])</h4>
                        @langGet('cities.FAQArea2Text2', [ 'cities' => $areaName ])
                        <ol>
                            <li><a href="https://amzn.to/345SqsM" title="Padlock"
                                   target="_blank">@langGet('cities.Padlock')</a></li>
                            <li><a href="https://amzn.to/2Pd2NqA" title="Earplugs"
                                   target="_blank">@langGet('cities.Earplugs')</a></li>
                            <li><a href="https://amzn.to/38fJELn" title="Sleeping Mask"
                                   target="_blank">@langGet('cities.SleepingMask')</a></li>
                            <li><a href="https://amzn.to/2LHU3pZ" title="Quick dry Towel"
                                   target="_blank">@langGet('cities.QuickDryTowel')</a></li>
                            <li><a href="https://amzn.to/348An57" title="Head Lamp"
                                   target="_blank">@langGet('cities.HeadLamp')</a></li>
                        </ol>
                        @langGet('cities.FAQArea2Text3', [ 'cities' => $areaName, 'WhatPackLink' => routeURL('articles', 'what-to-pack') ])
                    </div>
                </div>
            </div>
            <img src="{!! routeURL('images', 'faq-icon.svg') !!}" alt="#" class="faq-icon">
        </div>
    </section>
@stop

@section('pageBottom')

    @parent

    <script type="text/javascript">
        var citiesOptions = JSON.parse('{!! json_encode([
            'staffCountryInfos' => routeURL('staff-countryInfos'),
            'search' => $search,
            'mapMarker' => [
                'mapMarkerCities' => routeURL('images', 'mapMarker-cities.png'),
                'mapMarkerCitiesHighlighted' => routeURL('images', 'mapMarker-cities-highlighted.png'),
                'width' => \App\Models\CityInfo::CITY_MAP_MARKER_WIDTH,
                'height' => \App\Models\CityInfo::CITY_MAP_MARKER_HEIGHT,
                ],
            'googleMapUrl' => "//maps.googleapis.com/maps/api/js?v=3&key=" . urlencode(config('custom.googleApiKey.clientSide')) . "&callback=doAfterMapScriptIsLoaded&language=" . Languages::current()->otherCodeStandard('IANA'),
        ], JSON_HEX_APOS, JSON_HEX_QUOT) !!}');

        initializeCitiesPage({!! $countryInfo->id !!});

        @if ($mapPoints)
        function doAfterMapScriptIsLoaded() {
            displayMap({!! $mapBounds->json() !!}, {!! json_encode($mapPoints) !!},
                    {!! json_encode($pageCityInfo->country) !!}, {!! json_encode($pageCityInfo->region) !!},
                    {!! json_encode($isCityGroupPage ? $pageCityInfo->cityGroup : '') !!});
        }
        @endif
    </script>

    <script src="{{ mix('js/citiesVue.js')}}"></script>

@stop
