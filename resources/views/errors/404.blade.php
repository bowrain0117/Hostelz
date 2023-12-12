{{--
Input variables:
- $statusCode (optional)
- $errorMessage (optional)
--}}
<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts/default', ['showHeaderSearch' => true ])

@section('title', (@$statusCode ? $statusCode.' ' : '').'Ooops, this page is travelling - Hostelz.com')

@section('content')

<section class="pb-5 pb-sm-6 pb-md-7 pt-5 pt-md-6 bg-gray-100">
    <div class="container">
        <div class="text-center pt-5 pt-md-7 mt-4 mt-md-5 justify-content-around">
            <div class="col-lg-12 pb-3 pb-md-5">

                @if (@$errorMessage != '')
                    <h1 class="hero-heading h2"><i class="fa fa-exclamation-circle"></i> Sorry an error occurred...</h1>
                    <p class="">The error has been logged and we will look into it.</p>

                @elseif (@$statusCode == 404)
                    <h1 class="hero-heading h2">Ooops...looks like this page is currently travelling</h1>
                    {{-- {!! $statusCode !!} --}}
                @else
                    <h1 class="hero-heading h2"><i class="fa fa-exclamation-circle"></i> Sorry an error occurred...</h1>
                    <p class="">The error has been logged and we will look into it.</p>
                @endif

                <p class="">Please use our powerful hostel search engine. It will bring you to any destination you want.</p>
                <div class="">
                    <button class="btn btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>
                </div>

            </div>
        </div>
    </div>
</section>
    
@stop