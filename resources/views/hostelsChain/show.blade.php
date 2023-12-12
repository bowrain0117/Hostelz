<?php

Lib\HttpAsset::requireAsset('wishlistMain.js');
Lib\HttpAsset::requireAsset('indexMain.js');
Lib\HttpAsset::requireAsset('hostelChains.js');

$hostelChainCount = $hostelChain->listingsCount;
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', @langGet('SeoInfo.HostelChainSingleMetaTitle', [ 'hostelchain' => $hostelChain->name, 'hostelchaincount' => $hostelChainCount, 'year' => date("Y"), 'month' => date("M")]))

@section('header')
    @empty ($hostelChain->meta_title)
        <meta property="og:title"
              content="@langGet('SeoInfo.HostelChainSingleMetaTitle', [ 'hostelchain' => $hostelChain->name, 'hostelchaincount' => $hostelChainCount, 'year' => date("Y"), 'month' => date("M")])"/>
    @else
        <meta property="og:title" content="{{ $hostelChain->meta_title }}"/>
    @endempty

    @empty ($hostelChain->meta_description)
        <meta name="description"
              content="@langGet('SeoInfo.HostelChainSingleMetaText', [ 'hostelchain' => $hostelChain->name, 'hostelchaincount' => $hostelChainCount, 'year' => date('Y'), 'month' => date('M')])">
        <meta property="og:description"
              content="@langGet('SeoInfo.HostelChainSingleMetaText', [ 'hostelchain' => $hostelChain->name, 'hostelchaincount' => $hostelChainCount, 'year' => date('Y'), 'month' => date('M')])"/>
    @else
        <meta name="description" content="{{ $hostelChain->meta_description }}">
        <meta property="og:description" content="{{ $hostelChain->meta_description }}"/>
    @endempty

    @parent
@stop

@section('content')
    <section class="pt-3 pb-5 container">
        <div class="row flex-column flex-lg-row">
            <div class="col-12 col-lg-8 mb-lg-5 mb-4">
                <div class="breadcrumbs">
                    <ul class="breadcrumb black px-0 mx-sm-n3 mx-lg-0" vocab="http://schema.org/"
                        typeof="BreadcrumbList">
                        @breadcrumb(langGet('global.Home'), routeURL('home'))
                        @breadcrumb(langGet('HostelsChain.hostelsChains', ['year' => date("Y") ]), routeURL('hostelChain:index'))
                        @breadcrumb($hostelChain->name)
                    </ul>
                </div>

                <div class="mb-lg-2 pb-md-2 mx-sm-n3 mx-lg-0">
                    <h1 class="hero-heading h2" id="{{ $hostelChain->name }}">{{ $hostelChain->name }}</h1>
                    @if($hostelChain->description)
                        {!! $hostelChain->description !!}
                    @endif
                </div>
            </div>

            {{--sidebar--}}

            <div class="col-12 col-lg-4">
                <div class="pl-xl-4">
                    <!-- START Intro -->
                    <div class="card border-0 shadow mb-5">
                        <div class="card-header bg-gray-100 py-4 border-0">
                            <div class="media align-items-center mb-3">
                                <div class="media-body">
                                    <x-pictures.hostels-chain :pic="$hostelChain->pic" :alt="$hostelChain->name"/>
                                </div>
                            </div>
                            <div class="media align-items-center">
                                <div class="media-body">
                                    <p class="subtitle text-sm text-primary">At a glance</p>
                                    <h4 class="mb-0">About {{ $hostelChain->name }}</h4>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="fas fa-hotel"></i>
                                    <div class="ml-2">{{ $hostelChain->listingsCount }} Properties</div>
                                </li>
                                @if(!empty($hostelChain->instagram_link))
                                    <li class="mb-2 d-flex align-items-center">
                                        @include('partials.svg-icon', ['svg_id' => 'instagram-blue', 'svg_w' => '24', 'svg_h' => '25'])
                                        <a href="{{ $hostelChain->instagram_link }}" rel="nofollow"
                                           title="{{ $hostelChain->name }} {{ __('HostelsChain.instagram') }}"
                                           target="_blank" class="ml-2">
                                            {{ __('HostelsChain.instagram') }}
                                        </a>
                                    </li>
                                @endif
                                @if(!empty($hostelChain->website_link))
                                    <li class="mb-2 d-flex align-items-center">
                                        <img src="{{ routeURL('images', 'red-globe.svg') }}">
                                        <a href="{{ $hostelChain->website_link }}" rel="nofollow"
                                           title="{{ $hostelChain->name }} {{ __('HostelsChain.website') }}"
                                           target="_blank" class="ml-2">
                                            {{ __('HostelsChain.website') }}
                                        </a>
                                    </li>
                                @endif
                                @if(!empty($hostelChain->affiliate_links))
                                    <p class="mt-4 mb-5 mb-lg-0 text-center">
                                        <a class="btn btn-danger rounded px-5"
                                           href="{{ $hostelChain->affiliate_links }}"
                                           title="Book {{ $hostelChain->name }} here"
                                           target="_blank"
                                           rel="nofollow"
                                        >
                                            Book {{ $hostelChain->name }} here
                                        </a>
                                    </p>
                                @endif
                            </ul>
                        </div>
                    </div>
                    {{-- END Intro --}}

                    <!-- START Locations -->
                    <div class="card border-0 shadow mb-5">
                        <div class="card-header bg-gray-100 py-4 border-0">
                            <div class="media align-items-center">
                                <div class="media-body">
                                    <p class="subtitle text-sm text-primary">Locations</p>
                                    <h4 class="mb-0">Where can you stay at {{ $hostelChain->name }}?</h4>
                                </div>
                                @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '50', 'svg_h' => '50'])
                            </div>
                        </div>

                        <div class="card-body">
                            <table class="table text-sm mb-0">
                                <tbody>
                                    <?php
                                    $showcities = 8;
                                    $cityarray = [];
                                    ?>

                                @if($listings->isNotEmpty())
                                    @foreach ($listings as $listing)
                                            <?php
                                            $cityarray[$listing->city] = $listing->country; ?>
                                    @endforeach
                                    @foreach (array_slice($cityarray, 0,$showcities, true) as $city=>$country)
                                        <tr>
                                            <th class="pl-0 border-0">{{ $city }}, <span
                                                        class="font-weight-normal">{{ $country }}</span></th>
                                            <td class="pr-0 text-right border-0"></td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>

                            @if (count($cityarray) > $showcities)

                                <div id="heading">
                                    <div id="collapse" aria-labelledby="heading" class="collapse">
                                        <table class="table text-sm mb-0">
                                            <tbody>
                                            @foreach (array_slice($cityarray, $showcities,count($cityarray), true) as $city=>$country)
                                                <tr>
                                                    <th class="pl-0 border-0">{{ $city }}, <span
                                                                class="font-weight-normal">{{ $country }}</span></th>
                                                    <td class="pr-0 text-right border-0"></td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <a data-toggle="collapse" href="#collapse" aria-expanded="true"
                                       aria-controls="collapseTwo"
                                       class="accordion-link cl-text py-0 collapse-arrow-wrap icon-rounded icon-rounded-sm bg-second mx-auto"><i
                                                class="fas fa-angle-down text-white"></i><i
                                                class="fas fa-angle-up text-white"></i></a>
                                </div>
                            @endif

                        </div>
                    </div>
                    {{-- END Locations --}}
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gray-100 py-5 mt-2" id="vue-listings-card-slider">
        <div class="container">
            @if($listings->isNotEmpty())
                <div class="mb-md-5 mt-md-3 pb-3">
                    @foreach ($listings as $listing)
                        @php
                            $features = \App\Models\Listing\ListingFeatures::getDisplayValues($listing->compiledFeatures);
                            $review = $listing->getLiveReview();

                            $schema = (new App\Schemas\HostelSchema($listing, $review, $features))->getSchema();
                        @endphp

                        @push('schema-scripts')
                            {!! $schema !!}
                        @endpush

                        <div class="d-flex flex-column flex-lg-row mb-5 mb-lg-6 listing">
                            <div class="position-relative">
                                <div class="card-img-overlay-top d-flex justify-content-end">
                                    <x-wishlist-icon class="z-index-40" :listing-id="$listing->id"/>
                                    <comparison-icon :listing-id="{{ $listing->id }}"/>
                                </div>

                                <x-sliders.listing :picUrls="$picUrls[$listing->id]" :listing="$listing"/>

                            </div>
                            <div class="list-description mx-0 mx-lg-5">
                                <div class="d-flex flex-row justify-content-between">
                                    <div class="">
                                        <h3 class="font-weight-600 mr-0 mr-lg-2 my-2 listing__title">
                                            <a href="{!! $listing->getURL() !!}" target="_blank"
                                               class="text-decoration-none cl-text"
                                               title="{{{ $listing->name }}}"
                                               property="name"
                                               data-hostel-id="{{ $listing->id }}"
                                            >
                                                {{{ $listing->name }}}
                                            </a>
                                        </h3>
                                        <h4 class="my-1 pre-title">@include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25']) {{ $listing->city }}
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
                                    @php
                                        $combinedRatingCount = $listing->combinedRatingCount;
                                    @endphp
                                    <div class="pl-0 pl-lg-5 d-lg-none text-center mt-3">
                                        {{-- ** Combined Rating ** --}}
                                        @if ($listing->combinedRating)
                                            <div class="list-footer_rating d-flex flex-column align-items-center justify-content-center mb-3">
                                                <div class="hostel-card-rating hostel-card-rating-small mb-1">
                                                    {{ $listing->formatCombinedRating() }}
                                                </div>

                                                @if ($combinedRatingCount)
                                                    <div class="pre-title cl-subtext nowrap">
                                                        <span property="ratingCount">{!! $combinedRatingCount !!}</span> @langGet('city.Reviews')
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
                                            @if ($combinedRatingCount > 49)
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

                                        @if ($combinedRatingCount)
                                            <div class="text-sm cl-subtext nowrap">
                                                <span property="ratingCount">{!! $combinedRatingCount !!}</span> @langGet('city.Reviews')
                                            </div>
                                        @endif

                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else

                <p>No listings yet. We are on it! This is gonna be amazing!</p>

            @endif


        </div>
    </section>

    @stack('schema-scripts')

    @if ($pois)
        <section class="container py-5 mt-2" id="map-section">
            <h2>{{ __('cities.AllCitiesMapTitle', ['cities' => $hostelChain->name]) }}</h2>
            <div class="map-wrapper-450 mb-5 mb-sm-6">
                <div id="mapCanvas" class="h-100"></div>
            </div>
        </section>
    @endif

    @if (!empty($hostelChain->videoEmbedHTML))
        <section class="py-5 py-lg-6 bg-gray-100 container">
            <div class="row justify-content-around text-center mb-5">
                <div class="col-12 col-8">
                    <h2>{{ __('HostelsChain.hostelChainVideo', ['name' => $hostelChain->name]) }}</h2>
                    {!! $hostelChain->videoEmbedHTML !!}
                </div>
            </div>
        </section>
    @endif

    @include('hostelsChain.faq')
@stop

@section('pageBottom')

    <script src="{{ mix('js/citySlider.js')}}"></script>


    <script src="{{ mix('js/vue/modules/listings-card-slider.js')}}"></script>

    <script async defer
            src="//maps.googleapis.com/maps/api/js?key={!! urlencode(config('custom.googleApiKey.clientSide')) !!}&callback=mapScriptLoaded&language={!! App\Models\Languages::current()->otherCodeStandard('IANA') !!}"
            type="text/javascript">
    </script>

    @include('wishlist.modalWishlists')
    @include('wishlist.modalCreateWishlist')
    @include('wishlist.modalLogin')
    @include('wishlist.toasts')

    <script type="text/javascript">
        $(document).on("hostelz:frontUserData", function (e, data) {
            data.editURLFor = {target: 'hostelChain', id: {{ $hostelChain->id }}};
            return data;
        })

        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (data.editURL) {
                $('h1.hero-heading').after('<a class="d-block text-center text-decoration-underline pb-3" href="' + data.editURL + '">edit chain</a>');
            }
        });

        let citiesOptions = JSON.parse('{!! json_encode([
            'mapMarker' => [
                'mapMarkerBlue' => routeURL('images', 'mapMarker-blue.png'),
                'mapMarkerBlueHostelHighlighted' => routeURL('images', 'mapMarker-blue-highlighted.png'),
                'width' => \App\Models\CityInfo::CITY_MAP_MARKER_WIDTH,
                'height' => \App\Models\CityInfo::CITY_MAP_MARKER_HEIGHT,
                ],
        ], JSON_HEX_APOS, JSON_HEX_QUOT) !!}');

        displayMap({!! $pois['mapBounds']->json() !!}, {!! json_encode($pois['mapPoints']) !!});
    </script>
@stop