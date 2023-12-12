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

    <div class="container">
        <h2 class="text-primary title-3">{{ __('comparison.pickHostel') }}</h2>
        <h1 class="cl-dark font-weight-bold title-2 mb-4">{{ __('comparison.comparisonTool') }}</h1>

        <span>{{ __('comparison.comparisonDefaultText') }}</span>
        <span class="card-fav-icon ml-1 opacity-9 position-relative z-index-40 cursor-pointer comparison bg-light">
            @include('partials.svg-icon', ['svg_id' => 'comparison-tool', 'svg_w' => '24', 'svg_h' => '24'])
        </span>

        <div class="d-flex justify-content-center my-3">
            <button class="btn btn-primary tt-n js-open-search-location">
                @include('partials.svg-icon', ['svg_id' => 'search-icon-3', 'svg_w' => '24', 'svg_h' => '24'])
                <span class="ml-2">{{ __('comparison.comparisonSearchFor') }}</span>
            </button>
        </div>
    </div>


    <div id="vue-comparison">
        <hostel-cards-default></hostel-cards-default>
    </div>
    <script src="{{ mix('js/vue/modules/comparison.js')}}"></script>
@stop