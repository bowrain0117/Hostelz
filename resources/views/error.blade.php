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

                {{-- <button type="button" class="card-title js-open-search-location">Try it</button>
                <a style="color: #ff635c" class="card-title js-open-search-dates">Text</a>
                <img class="w-50" style="w-50" src="{!! routeURL('images', '404-hero.jpg') !!}"> --}}

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

               {{--  <div class="search-bar mt-5 p-3 p-lg-4 rounded">
                    <form action="{!! routeURL('search') !!}" method="get" target="_top">
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="d-flex align-items-center form-group no-divider border rounded mb-0 mr-sm-n3 mr-md-0">
                                    <input type="text" name="search" placeholder="{{{ langGet('index.EnterAName') }}}" class="websiteSearch form-control border-0 shadow-0">
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-primary btn-block mt-2 mt-sm-0 text-nowrap"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>
                            </div>
                        </div>
                    </form>
                </div> --}}
            </div>
        </div>
    </div>
</section>
    
@stop