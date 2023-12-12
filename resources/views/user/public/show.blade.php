<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', $user->nickname ? "{$user->nickname} - Hostel Portfolio on Hostelz.com" : 'Hostel Portfolio on Hostelz.com')

@section('header')
    <meta name="description" content="Hostel Portfolio of {{$user->nickname}} on Hostelz.com. Track your reservations and get access to exclusive travel guides.">
    @parent
@stop

@section('headerJsonSchema')
    {!! $schema->toScript() !!}
@stop

@section('content')

    <section class="py-6 bg-gray-100">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-2 mb-5 mb-lg-0 text-center">
                    @if ($user->profilePhoto)
                        <img alt="Hostel Portfolio of {{$user->nickname}} on Hostelz.com"
                             title="Hostel Portfolio of {{$user->nickname}} on Hostelz.com"
                             class="avatar mr-2 avatar-xl avatar-border-white"
                             src="{{ $user->profilePhoto->url(['thumbnails']) }}">
                    @else
                        <div class="avatar mr-2 bg-gray-800 border border-white">
                            @include('partials.svg-icon', ['svg_id' => 'user-icon-dark', 'svg_w' => '44', 'svg_h' => '48'])
                        </div>
                    @endif
                </div>
                <div class="col-12 col-lg-6 mb-5 mb-lg-0">
                    <div class="cl-text ml-lg-4">
                            <div class="font-weight-600 h3">{{ $user->nickname }}
                                @if ($user->birthDate)
                                    ({{ Carbon\Carbon::parse($user->birthDate)->diff(Carbon\Carbon::now())->format('%y') }})
                                @endif
                            </div>
                            
                            @isset($user->bio)
                                <div class="cl-body mb-3">{{ $user->bio }}</div>
                            @endisset

                            @if($articles->isNotEmpty())
                                <div class="cl-body mb-3">
                                    {{$user->nickname}} is a travel writer at Hostelz.com, crafting captivating tales of travel and adventure.
                                </div>
                            @endif


                            <div class="row">
                                <div class="col">
                                    @if ($user->homeCountry)
                                        <p><b>I am from:</b> {{ $user->homeCountry }}</p>
                                    @endif
                                </div>
                                <div class="col-auto">
                                    @if($user->languages)
                                    <p><b>I speak:</b>
                                    @foreach($user->languages as $lang)
                                        @if (!$lang)
                                            @continue
                                        @endif
                                        
                                        <span class="list-inline-item">
                                            <img data-src="{!! routeURL('images', 'flags/' . $lang . '.svg') !!}"
                                                class="lazyload"
                                                src="" alt="flag" style="width: 24px"/>
                                        </span>
                                        
                                    @endforeach
                                    @endif
                                </p>
                                </div>
                              </div>
                        </div>
                </div>

                <div class="col-12 col-lg-4">
                    <h6 class="text-uppercase text-dark mb-3 text-center">My Hostel Portfolio</h6>
                    <div class="row">
                        <div class="col-4 col-lg-4 py-4 mb-3 mb-lg-0 text-center rounded">
                            <div class="px-0 px-lg-3">
                                <div class="icon-rounded bg-gray-200 mb-3 h3">{!! $user->points !!}</div>
                                <p class="mb-3 text-sm">Pointz</p>
                            </div>
                        </div>

                        @if($articlesCount)
                            <div class="col-4 col-lg-4 py-4 mb-3 mb-lg-0 text-center rounded">
                                <div class="px-0 px-lg-3">
                                    <div class="icon-rounded bg-gray-200 mb-3 h3">{{ $articlesCount }}</div>
                                    <p class="mb-3 text-sm">Articles written</p>
                                </div>
                            </div>
                        @endif

                        <div class="col-4 col-lg-4 py-4 mb-3 mb-lg-0 text-center rounded">
                            <div class="px-0 px-lg-3">
                                <div class="icon-rounded bg-gray-200 mb-3 h3">{{ $user->approvedRatings }}</div>
                                <p class="mb-3 text-sm">Reviews written</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container mt-5 mt-lg-6">
        <div class="row">
            <div class="col-12 col-lg-12">

                @if($articles->isNotEmpty())
                    <section class="py-4 py-sm-5">
                        <div class="container">
                            <div class="mb-4">
                                <h3>My Articles</h3>
                            </div>
                            <div class="row mb-5">
                                @foreach($articles as $article)
                                    @include('articles.card', ['item' => $article])
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                @if ($user->favoriteHostelsList->isNotEmpty())
                    <section class="">
                        <h3 class="cl-text mb-4">My Favorite Hostels</h3>
                        <div class="row">
                            @foreach($user->favoriteHostelsList as $listing)
                                <div class="col-12 col-md-3 mb-4">
                                    <div class="d-flex w-100 mb-4 mb-lg-0">
                                        <div class="hostel-card bg-white w-100 hover-animate">
                                            <div class="hostel-card-img">
                                                <a class="" target="_blank"
                                                   href="{{ $listing->getURL() }}"
                                                   title="{{ $listing->name }}"
                                                >
                                                    <img class="hostel-card-img w-100"
                                                         src="{{ $listing->thumbnailURL() }}"
                                                         alt="{{ $listing->name }}"
                                                         title="{{ $listing->name }}"
                                                    >
                                                </a>
                                            </div>

                                            <div class="hostel-card-body p-3">
                                                <div class="hostel-card-body-header d-flex justify-content-between align-items-stretch">
                                                    <h5 class="hostel-card-title tx-body font-weight-bold mb-0">
                                                        <a class="cl-text" target="_blank"
                                                           href="{{ $listing->getURL() }}">{{ $listing->name }}</a>
                                                    </h5>
                                                    <div class="hostel-card-rating hostel-card-rating-small flex-shrink-0">
                                                        {{ $listing->formatCombinedRating() }}
                                                    </div>
                                                </div>

                                                <div class="my-1 pre-title">
                                                    @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                                                    {{{ $listing->city }}}, {{{ $listing->country }}}
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($user->dreamDestinationsList->isNotEmpty())
                    <section class="py-2 my-4">
                        <h3 class="cl-text mb-4">My Dream Destinations</h3>
                        <div class="row">
                            @foreach($user->dreamDestinationsList as $city)
                                <div class="col-12 col-md-3 mb-4">
                                    <div class="card card-poster dark-overlay hover-animate mb-4 mb-lg-0 position-relative">
                                        <a href="{!! $city->getURL() !!}" class="tile-link" target="_blank"
                                           title=""></a>

                                        @if ( $city->thumbnail )
                                            <img src="{{ $city->thumbnail }}"
                                                 class="swiper-lazy bg-image"
                                                 alt="{!! $city->translation()->city !!} Hostels @langGet('bookingProcess.from') ${{ $city->lowestDormPrice }}">
                                        @endif

                                        <div class="card-body overlay-content text-center">
                                            <h6 class="card-title text-shadow text-uppercase text-white">{!! $city->translation()->city !!}</h6>
                                            <p class="card-text text-sm"></p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

            </div>
        </div>
    </section>

@stop