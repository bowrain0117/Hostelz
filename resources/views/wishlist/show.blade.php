<?php

Lib\HttpAsset::requireAsset('wishlistMain.js');
Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('wishlist.Wishlist').' '.$wishlist->name.' - Hostelz.com')

{{-- {!! langGet('city.BackpackingTo', [ 'city' => $cityInfo->translation()->city]) !!} --}}

@section('header')
    <meta name="description" content="wishlist description">
    @parent
@stop

@section('content')

    @include('user.navbarDashboard')

    <div class="pt-3 pb-5 container">

        <div class="breadcrumbs">
            <ol class="breadcrumb black" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
                {!! breadcrumb(langGet('wishlist.Wishlists' ), routeURL('wishlist:index') ) !!}
                {!! breadcrumb( $wishlist->name ) !!}
            </ol>
        </div>
        <h1 class="hero-heading h2">@langGet('wishlist.savedList'): <strong>{{ $wishlist->name }}</strong></h1>

        <section class="pb-5">
            @if($listings->isNotEmpty())
                <div class="mb-md-5 mt-md-3 pb-3">
                    @foreach ($listings as $listing)
                        <div class="d-flex flex-column flex-lg-row mb-5 mb-lg-6 listing wishlistDashboard">
                            <a href="{!! $listing->getURL() !!}" title="{{{ $listing->name }}}" target="_blank"
                               class="">
                                <div class="hostel-tile__img position-relative">
                                    <div class="card-img-overlay-top text-right">
                                        <x-wishlist-icon class="z-index-40" :listing-id="$listing->id"/>
                                    </div>
                                    <div class="listingCardSlider" property="image"
                                         content="{!! $listing->thumbnailURL() !!}">
                                        <img class="hostel-tile__img" src="{!! $listing->thumbnailURL() !!}"
                                             alt="{{ $listing->name }}" style="object-fit: cover;"
                                             style="background-image: url({!! $listing->thumbnailURL() !!});">
                                    </div>
                                </div>
                            </a>
                            <div class="list-description mx-0 mx-lg-5">
                                <div class="d-flex flex-row justify-content-between">
                                    <div class="">
                                        <h3 class="font-weight-600 mr-0 mr-lg-2 my-2 listing__title">
                                            <a href="{!! $listing->getURL() !!}" target="_blank"
                                               class="text-decoration-none cl-text" title="{{{ $listing->name }}}"
                                               property="name">{{{ $listing->name }}}</a>
                                        </h3>
                                        <h4 class="my-1 pre-title">@include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25']){{ $listing->city }}
                                            , {{ $listing->country }}</h4>

                                        @if ($listing->cityAlt != ''
                            || (isset($listing->compiledFeatures['breakfast']) && $listing->compiledFeatures['breakfast'] === 'free')
                            || (isset($listing->compiledFeatures['extras']) && in_array('privacyCurtains', $listing->compiledFeatures['extras']))
                        )
                                            <div class="neighborhood mb-2 small">
                                                {{-- @if ($listing->cityAlt != '') --}}
                                                {{--    <span class="my-1 pre-title"> --}}
                                                {{--        @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25']) --}}
                                                {{--            {{{ $listing->cityAlt }}} --}}
                                                {{--    </span> --}}
                                                {{-- @endif --}}

                                                {{-- <br> --}}

                                                @if (isset($listing->compiledFeatures['breakfast']) && $listing->compiledFeatures['breakfast'] === 'free')
                                                    <span class="delimiter mt-2 d-inline-block"><i
                                                                class="fa fa-coffee w-1rem mr-1"></i> @langGet('city.FeatureFreeBreakfast')</span>
                                                @endif

                                                @if (isset($listing->compiledFeatures['extras']) && in_array('privacyCurtains', $listing->compiledFeatures['extras']))
                                                    <span class="delimiter mt-2 d-inline-block"><i
                                                                class="fa fa-person-booth w-1rem mr-1"></i> @langGet('city.PrivacyCurtains')</span>
                                                @endif
                                            </div>
                                        @endif

                                    </div>
                                    <div class="pl-0 pl-lg-5 d-lg-none text-center mt-3">
                                        {{-- ** Combined Rating ** --}}
                                        @if ($listing->combinedRating)
                                            <div class="list-footer_rating d-flex flex-column align-items-center justify-content-center mb-3">
                                                <div class="hostel-card-rating hostel-card-rating-small mb-1">
                                                    {{ $listing->formatCombinedRating() }}
                                                </div>

                                                @if ($listing->combinedRatingCount)
                                                    <div class="pre-title cl-subtext nowrap">
                                                        <span property="ratingCount">{!! $listing->combinedRatingCount !!}</span> @langGet('city.Reviews')
                                                    </div>
                                                @endif

                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    @if($listing->isTypeOfHostel())
                                        @php $features = \App\Models\Listing\ListingFeatures::getDisplayValues($listing->compiledFeatures) ?: []; @endphp
                                        @if (isset($features['goodFor'][langGet("ListingFeatures.categories.goodFor")]))
                                            @foreach ($features['goodFor'][langGet("ListingFeatures.categories.goodFor")] as $feature)
                                                @if ($feature['displayType'] == 'labelValuePair')
                                                    <img src="{!! routeURL('images', 'info.svg') !!}" alt="#"
                                                         class="mr-2" style="height:15px">
                                                    <p class="display-4 mb-0">{!! $feature['label'] !!}: <span
                                                                class="font-weight-bolder">{{{ $feature['value'] }}}</span>
                                                    </p>
                                                @else
                                                    <span class="pre-title listing-feature">{!! $feature['label'] !!} <span
                                                                class="font-weight-bolder">@if (@$feature['value'] != '')
                                                                ({{{ $feature['value'] }}})
                                                            @endif</span></span>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endif

                                    {{-- boutiqueHostel --}}
                                    @if(isset($listing->boutiqueHostel) && $listing->boutiqueHostel === 1)
                                        <span class="pre-title listing-feature">{{ langGet('ListingFeatures.forms.fieldLabel.boutiqueHostel') }}</span>
                                    @endif
                                </div>

                                <div class="mb-3 tx-small" property="description">
                                    @if ($listing->snippetFull)
                                        {!! wholeWordTruncate($listing->snippetFull, 100) !!}
                                    @else
                                        {{ $listing->address }} ...
                                    @endif
                                    <a href="{!! $listing->getURL() !!}" title="{{{ $listing->name }}}" target="_blank"
                                       class="font-weight-600 text-lowercase">@langGet('city.more')</a>
                                </div>
                            </div>

                            <div class="pl-0 pl-lg-5 d-none d-lg-block border-left text-center ml-lg-auto">
                                {{-- ** Combined Rating ** --}}
                                @if ($listing->combinedRating)
                                    <div class="list-footer_rating d-flex flex-column align-items-center justify-content-center mb-3">
                                        <div class="mb-1 pre-title">
                                            @if ($listing->combinedRatingCount > 49)
                                                @if ($listing->combinedRating / 10 > 9.0 )
                                                    <span class="nowrap">@langGet('listingDisplay.1stBestRating')</span>
                                                @elseif ($listing->combinedRating / 10 > 8.4 )
                                                    <span class="nowrap">@langGet('listingDisplay.2ndBestRating')</span>
                                                @elseif ($listing->combinedRating / 10 > 7.9 )
                                                    <span class="nowrap">@langGet('listingDisplay.3rdBestRating')</span>
                                                @else
                                                    <span class="nowrap">@langGet('listingDisplay.CombinedRatingTitleTotal')</span>
                                                @endif
                                            @else
                                                <span class="nowrap">@langGet('listingDisplay.CombinedRatingTitleTotal')</span>
                                            @endif
                                        </div>

                                        <div class="hostel-card-rating mb-1">
                                            <span class="combinedRating"
                                                  content="{!! round($listing->combinedRating / 10, 1) !!}">{!! $listing->formatCombinedRating() !!}</span>
                                        </div>

                                        @if ($listing->combinedRatingCount)
                                            <div class="text-sm cl-subtext nowrap">
                                                <span property="ratingCount">{!! $listing->combinedRatingCount !!}</span> @langGet('city.Reviews')
                                            </div>
                                        @endif

                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p>@langGet('wishlist.noListings')</p>
                <p>Search on Hostelz.com for great hostels and start adding them to your wishlist.</p>
            @endif
        </section>
        <div class="">
            <button class="btn btn-warning rounded px-4 px-sm-5" data-toggle="modal"
                    data-target="#deleteWishlistModal">@langGet('wishlist.deleteList')</button>
        </div>
    </div>

@stop

@section('pageBottom')

    @parent

    <div class="modal fade" id="deleteWishlistModal" tabindex="-1" role="dialog" aria-labelledby="deleteWishlistModal"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">@langGet('wishlist.deleteList')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="deleteWishListForm" action="@routeURL('wishlist:destroy', $wishlist)" method="POST">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        @langGet('wishlist.modalDeleteBodyText') <strong>{{ $wishlist->name }}</strong>?
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">@langGet('global.Cancel')</button>
                    <button type="submit" class="btn btn-success"
                            form="deleteWishListForm">@langGet('wishlist.modalDeleteBtnYes')</button>
                </div>
            </div>
        </div>
    </div>

    @include('wishlist.modalCreateWishlist')

    @include('wishlist.modalWishlists')

    @include('wishlist.toasts')

@stop