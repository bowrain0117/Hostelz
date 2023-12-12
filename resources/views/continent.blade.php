<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true  ])

@section('title', langGet('SeoInfo.ContinentsMetaTitle', ['area' => $continentInfo->translation(), 'year'=>date("Y")]))
             
@section('header')
	<meta name="description" content="{!! langGet('SeoInfo.ContinentsMetaDescription', [ 'area' => $continentInfo->translation(), 'year'=>date('Y')]) !!}">
    <meta property="og:title" content="{!! langGet('SeoInfo.ContinentsMetaTitle', ['area' => $continentInfo->translation(), 'year'=>date("Y")]) !!}" />
    <meta property="og:description" content="{!! langGet('SeoInfo.ContinentsMetaDescription', [ 'area' => $continentInfo->translation(), 'year'=>date('Y')]) !!}" />

@stop

@section('content')

    {{--Breadcrumb/Title--}}
    <section>
        <div class="container">
        	<ul class="breadcrumb black px-0 mx-sm-n3 mx-lg-0">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb($continentInfo->translation()) !!}
            </ul>
            <h1 class="mb-4">{!! langGet('global.areaHostels', [ 'area' => $continentInfo->translation(), 'year'=>date('Y')]) !!}</h1>
        	@langGet('continents.IntroText', [ 'continent' => $continentInfo->translation()])
        </div>
    </section>

    {{--<!--Map-->
    <section class="container">
        @if (0)
            <div class="map-wrapper-450 mb-5">
                <div id="categoryMap" class="h-100"></div>
            </div>
        @endif     
    </section>--}}

    <!--Countries List-->
    <section class="container mt-4 mt-sm-5 mb-7 mb-md-8 mb-lg-9">

        @foreach ($countryInfos as $countryInfo)

            <div class="shadow rounded py-4 px-4 px-lg-5 mb-5" >
                <div class="row" style="min-height: 158px">
                    <a class="col-md-2 col-lg-2 col-sm-12 flex-center flex-column mb-3 mb-md-0"
                       title="{!! __('continents.HostelsInCountry', ['country' => $countryInfo->translation()->country]) !!}"
                       href="{!! $countryInfo->getURL() !!}"
                    >
                        <img src="{!! ($countryInfo->countryCode()) ? routeURL('images', 'flags/' . strtolower($countryInfo->countryCode()) . '.svg') : routeURL('images', 'flags/globe.svg'); !!}"
                             alt="{!! __('continents.HostelsInCountry', ['country' => $countryInfo->translation()->country]) !!}"
                             class="mb-4 img-fluid"
                             style="max-width: 100px;"
                        >
                        <h4 class="text-dark">{!! $countryInfo->translation()->country !!}</h4>
                    </a>
                    <div class="shadowed-right-side col-lg-8 col-md-7 col-sm-12">
                        <div class="column font-weight-bolder mb-md-n3 h-100" style="column-fill: balance; ">

                            @foreach ($cityInfos[$countryInfo->country] as $cityInfo)
                                <p><a class="text-dark mr-2"  title="@langGet('city.HostelsInCity', ['city' => $cityInfo->translation()->city])" href="{!! $cityInfo->getURL() !!}">{!! $cityInfo->translation()->city !!}</a></p>
                            @endforeach

                        </div>
                    </div>

                    @if ($countryInfo->cityCount > $cityInfos[$countryInfo->country]->count())
                        <div class="col-lg-2 col-md-3 col-sm-12 flex-center flex-column mt-sm-3">
                            <p class="mb-4">@langGet('continents.CountMoreCities' , ['count' => $countryInfo->cityCount])</p>
                            <a href="{!! $countryInfo->getURL() !!}" title="@langGet('continents.HostelsInCountry', ['country' => $countryInfo->translation()->country])" class="btn py-2 px-4 btn-outline-primary bg-primary-light tt-n font-weight-600">@langGet('continents.SeeFullList', ['country' => $countryInfo->translation()->country])</a>
                        </div>
                    @endif

                </div>
            </div>
        @endforeach
    </section>
@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop
