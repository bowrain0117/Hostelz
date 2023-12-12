<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts.default', [ 'showHeaderSearch' => true ] )

@section('title', 'Hostelz.com - '.htmlentities(langGet('SeoInfo.IndexMetaTitle', [ 'TotalHostelCount' => $hostelCount, 'year' => date("Y")])))

@section('header') 
    @include('index.indexHeader')
@stop

@section('bodyAttributes') class="index-page" @stop

@section('content')

    @include('index.indexHero')

    @include('index.indexCompareList') 

    @include('index/indexAdvantages')

    @include('index.indexFeaturedCities')

{{--        @include('listings/listingsRow', ['listings' => $nearYouListings, 'title' => 'Places to stay nearby', 'subtitle' => 'Hostels near you'])--}}

	@include('index.indexTestimonials')

    @include('index.indexBlog')

@stop

@section('pageBottom')
    @php
        $indexOptions = [
            "searchAutocompleteURL" =>  routeURL('searchAutocomplete'),
        ];
    @endphp

    <script>
      var indexOptions = JSON.parse('{!! json_encode($indexOptions, JSON_HEX_APOS, JSON_HEX_QUOT) !!}');
    </script>
@stop
