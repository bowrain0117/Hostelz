<?php
Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', ['showHeaderSearch' => true])

@section('title')@yield('login-title')@stop

@section('header')
<meta name="description" content="@yield('login-description')">@stop

@section('content')

    <div class="container-fluid px-3">
        <div class="row min-vh-100">
            <div class="col-md-8 col-lg-6 col-xl-5 d-flex align-items-center">
                <div class="w-100 px-md-5 px-xl-6 position-relative py-5">
                    <div class="mb-5">
                        <h1 class="h3">@yield('login-header')</h1>
                    </div>
                    @yield('login-form')
                </div>
            </div>

            <div class="col-md-4 col-lg-6 col-xl-7 d-flex align-items-center dark-overlay">
                <div style="background-image: url({!! routeURL('images', 'signup-hostels.jpg') !!});" class="h-100 mr-n3 bg-image bg-cover"></div>
                <div class="overlay-content">
                    <div class="row align-items-center justify-content-center py-7 text-center">
                        <div class="col-lg-4 mb-md-0 mb-2">
                            <span class="hover-animate card mb-lg-0 mb-3 border-0 shadow-lg">
                                <div class="card-body">
                                    <h3>@include('partials.svg-icon', [
                                        'svg_id' => 'green-check',
                                        'svg_w' => '24',
                                        'svg_h' => '24',
                                    ]) Track your Reservations</h3>
                                    <p class="text-sm">All in one place: Track your reservations from Hostelworld and Booking.com
                                        effortlessly.</p>
                                </div>
                            </span>
                        </div>

                        <div class="col-lg-4 mb-md-0 mb-2">
                            <span class="hover-animate card mb-lg-0 mb-3 border-0 shadow-lg">
                                <div class="card-body">
                                    <h3>@include('partials.svg-icon', [
                                        'svg_id' => 'green-check',
                                        'svg_w' => '24',
                                        'svg_h' => '24',
                                    ]) Unlock Hidden Gems</h3>
                                    <p class="text-sm">After tracking your reservation, you get access to our exclusive destination guides.
                                    </p>
                                </div>
                            </span>
                        </div>

                        <div class="col-lg-4 mb-md-0 mb-2">
                            <span class="hover-animate card mb-lg-0 mb-3 border-0 shadow-lg">
                                <div class="card-body">
                                    <h3>@include('partials.svg-icon', [
                                        'svg_id' => 'green-check',
                                        'svg_w' => '24',
                                        'svg_h' => '24',
                                    ]) Absolutely Free: <br><span
                                            class="bg-primary font-weight-bold rounded px-2 text-white">Pluz</span></h3>
                                    <p class="text-sm">Discover our PLUZ travel tips you won't find anywhere else.</p>
                                </div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('pageBottom')
    @parent

    <script>
        initializeTopHeaderSearch();
    </script>
@stop
