<?php
Lib\HttpAsset::requireAsset('booking-main.js');
Lib\HttpAsset::requireAsset('comparison.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', __('SeoInfo.ComparisonMetaTitle'))

@section('header')
    <meta name="description" content="{{ __('SeoInfo.ComparisonMetaDescription') }}">
@stop

@section('content')

    <div id="vue-comparison">
        <comparison :listings="{{ $listings }}" :features="{{ $listingsFeatures }}"></comparison>
    </div>
    <script src="{{ mix('js/vue/modules/comparison.js')}}"></script>

    <div id="comparePrices" class="compare-prices mt-4">
        <h3 class="p-0 cl-dark font-weight-bold mb-4 text-center">Add your Dates & Compare Prices</h3>

        <div class="mb-3 mt-2 selected-filters container-search-wrap py-2 container">
            @include('comparison.comparisonSearch')
        </div>

        <div id="compare" class="container"></div>
    </div>

    @if ($pois)
        <section class="container mt-4" id="map-section">
            <h2>{{ __('cities.AllCitiesMapTitle', ['cities' => 'compared']) }}</h2>
            <div class="map-wrapper-450 mb-5 mb-sm-6">
                <div id="mapCanvas" class="h-100"></div>
            </div>
        </section>
    @endif

@stop

@section('pageBottom')

    <?php
    $listingsBookingSearch = routeURL('listingsBookingSearch', ['']);
    ?>
    <script type="text/javascript">
        let listingsBookingSearchUrl = '{{ $listingsBookingSearch }}'

        let citiesOptions = JSON.parse('{!! json_encode([
            'mapMarker' => [
                'mapMarkerBlue' => routeURL('images', 'mapMarker-blue.png'),
                'mapMarkerBlueHostelHighlighted' => routeURL('images', 'mapMarker-blue-highlighted.png'),
                'width' => \App\Models\CityInfo::CITY_MAP_MARKER_WIDTH,
                'height' => \App\Models\CityInfo::CITY_MAP_MARKER_HEIGHT,
                ],
        ], JSON_HEX_APOS, JSON_HEX_QUOT) !!}');

        function initMap() {
            displayMap({!! $pois['mapBounds']->json() !!}, {!! json_encode($pois['mapPoints']) !!});
        };

    </script>

    <script async defer
            src="//maps.googleapis.com/maps/api/js?key={!! urlencode(config('custom.googleApiKey.clientSide')) !!}&callback=initMap&language={!! App\Models\Languages::current()->otherCodeStandard('IANA') !!}"
            type="text/javascript">
    </script>

@stop