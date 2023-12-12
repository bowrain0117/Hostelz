<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.HostelChainsMetaTitle', [ 'year' => date("Y") ]))

@section('header')
    <meta name="description" content="@langGet('SeoInfo.HostelChainsMetaText', ['year' => date('Y'), 'month' => date('M') ])">
    <meta property="og:title" content="@langGet('SeoInfo.HostelChainsMetaTitle', [ 'year' => date("Y") ])" />
    <meta property="og:description" content="@langGet('SeoInfo.HostelChainsMetaText', ['year' => date('Y'), 'month' => date('M') ])" />
@parent
@stop

@section('content')
    <section class="pt-3 pb-5 container">
        <div class="breadcrumbs">
            <ul class="breadcrumb black" vocab="http://schema.org/" typeof="BreadcrumbList">
                @breadcrumb(langGet('global.Home'), routeURL('home'))
                @breadcrumb(langGet('HostelsChain.hostelsChains', ['year' => date("Y") ]))
            </ul>
        </div>
        <div class="mb-lg-2 pb-md-2 mx-sm-n3 mx-lg-0">
            <h1 class="hero-heading h2" id="allhostels">@langGet('HostelsChain.hostelsChains', ['year' => date("Y") ])</h1>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <p>The worlds best and coolest Hostel Chains in the world at a glance. We list for you the worlds most popular chains from all continents that you will encounter during your travels around the globe.</p>
                    <p>Take a closer look at the different chains out there. You will notice the different style and type of hostel. Some are absolutely high-quality boutique hostels, while others are focused on providing you the perfect environment for an unforgettable party hostel experience.</p>
                    <p>We summarized the worlds top hostel chains for you. As always, compare hostel prices with Hostelz.com to save money and travel longer. Let’s get ready for legendary Party Hostels and unique Luxury Hostels.</p>
                    <p class="js-show-if-not-login"><a href="#signup" data-smooth-scroll="">Sign up with Hostelz.com</a> and get access to exclusive hostel content and much more.</p>
                </div>
                <div class="col-md-4">
                    <img class="w-100" src="{!! url('images', 'best-hostel-chains.jpg') !!}" alt="@langGet('SeoInfo.HostelChainsMetaTitle', [ 'year' => date("Y") ])" title="@langGet('SeoInfo.HostelChainsMetaTitle', [ 'year' => date("Y") ])">
                </div>
            </div>
        </div>
    </section>
    <section class="bg-gray-100 py-5 mt-2">
        <div class="container">
            @if($chains->isNotEmpty())
                <div class="row">
                    @foreach($chains as $chain)
                        @php
                            $schema = (new App\Schemas\HostelChainSchema($chain))->getSchema();
                        @endphp

                        @push('schema-scripts')
                            {!! $schema !!}
                        @endpush
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-5">
                            <div class="card h-100 border-0 shadow">
                                <div class="card-img-top overflow-hidden ">
                                    <a href="{!! $chain->path !!}" title="{!! $chain->name !!}" class="d-flex align-items-center justify-content-center bg-gray-400 img-cover-wrap" style="height: 180px;">
                                        <x-pictures.hostels-chain :pic="$chain->pic" :alt="$chain->name"/>
                                    </a>
                                </div>
                                <div class="card-body d-flex align-items-center">
                                    <div class="w-100">
                                        <h6 class="card-title"><a href="{!! $chain->path !!}" class="text-decoration-none text-dark" title="{!! $chain->name !!}">{!! $chain->name !!}</a></h6>
                                        <div class="d-flex card-subtitle">
                                            <p class="flex-grow-1 mb-0 text-sm">
                                                <i class="fas fa-hotel"></i> {{ $chain->listingsCount }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p>No Hostel Chains listed yet. We are on it! This is gonna be amazing!</p>
            @endif
            <div class="py-5">
                <p><b>Do you manage a Hostel Chain?</b> Then claim all your listings on Hostelz. Increase your sales, update your profiles, and so much more. <a href="@routeURL('contact-us', [ 'contact-form', 'listings'])" title="claim your listings">Get in touch with us</a>.</p>
            </div>
        </div>
    </section>

    @stack('schema-scripts')
@stop
