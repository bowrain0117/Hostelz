<div class="mb-3 mb-lg-5 border-bottom pb-3 pb-lg-5">
    <div class="d-flex align-items-lg-center justify-content-between mb-2">
        <div>
            <h1 class="title-2 cl-text">{{{ $listing->name }}}, {!! $cityData->city!!}</h1>

            @if ($listing->cityAlt != '')
                @if (!in_array('isClosed', $listingViewOptions))
                    <a href="#contact" class="d-block d-lg-none" data-smooth-scroll="">
                    <span class="neighborhood small">
                        <span class="my-1 pre-title cl-subtext">
                            @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                            {{{ $listing->cityAlt }}}
                        </span>
                    </span>
                    </a>
                @endif
            @endif
        </div>

        @if (!in_array('isClosed', $listingViewOptions) || !$listing->onlineReservations)
            <div class="d-flex flex-row align-items-start">

                <div style="font-size: 20px;" class="d-none d-lg-flex mr-2 vue-comparison-icon">
                    <x-wishlist-icon class="z-index-40" :listing-id="$listing->id"/>
                    <comparison-icon :listing-id="{{ $listing->id }}"/>
                </div>

                <div class="d-flex flex-column align-items-center ">
                    @if ($listing->combinedRatingCount > 0)
                        @if ($importedRatingScoreCount)
                            <a href="#ratings" class="" data-smooth-scroll="">
                                <div class="hostel-card-rating mb-1">
                                    <span class="combinedRating"
                                          content="{!! round($listing->combinedRating / 10, 1) !!}">{!! $listing->formatCombinedRating() !!}</span>
                                </div>
                            </a>
                        @else
                            <div class="hostel-card-rating mb-1">
                                <span class="combinedRating"
                                      content="{!! round($listing->combinedRating / 10, 1) !!}">{!! $listing->formatCombinedRating() !!}</span>
                            </div>
                        @endif
                    @endif

                    @if ($listing->combinedRatingCount)
                        <div class="text-sm cl-subtext nowrap">
                            <span property="ratingCount">{!! $listing->combinedRatingCount !!}</span> @langGet('city.Reviews')
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="mb-3">
        @if ($listing->cityAlt != '')
            @if (!in_array('isClosed', $listingViewOptions))
                <a href="#contact" class="d-none d-lg-inline-block" data-smooth-scroll="">
                    <span class="neighborhood small">
                        <span class="my-1 pre-title cl-subtext">
                            @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                            {{{ $listing->cityAlt }}}
                        </span>
                    </span>
                </a>
            @endif
        @elseif (isset($importedRatingScores['average']['location']) && $importedRatingScores['average']['location'] >= 87)
            <a href="#contact" data-smooth-scroll="" class="text-dark">
                <span class="py-2 px-2 px-md-2 mx-1 mx-md-1 bg-gray-100 rounded small"><i
                            class="fa fas fa-map-marker-alt fa-fw mr-2"></i><span
                            class="font-weight-600 display-4">@langGet('listingDisplay.TopLocation')</span></span></a>
        @elseif (isset($importedRatingScores['average']['location']) && $importedRatingScores['average']['location'] >= 80)
            <a href="#contact" data-smooth-scroll="" class="text-dark">
                <span class="py-2 px-2 px-md-2 mx-1 mx-md-1 bg-gray-100 rounded small"><i
                            class="fa fas fa-map-marker-alt fa-fw mr-2"></i><span
                            class="font-weight-600 display-4">@langGet('listingDisplay.GoodLocation')</span></span></a>
        @endif
    </div>


    @if (isset($features['goodFor'][__("ListingFeatures.categories.goodFor")]))
        <div id="vue-listings-features-slider">
            <slider
                    :features="{{ json_encode($features['goodFor'][__("ListingFeatures.categories.goodFor")]) }}"
                    :listing="{{ json_encode($listing) }}"
                    :src="'{{ routeURL('images', 'info.svg') }}'"
            ></slider>
        </div>
    @endif
</div>

<?php
$isShowCheapestHostel = ($cityInfo && $cityInfo->hostelCount > 1 && $cityInfo->cheapestHostel == $listing->id);
$isShowBoutiqueHostel = (isset($listing->boutiqueHostel) && $listing->boutiqueHostel === 1);
$isShowTopRated = ($cityInfo && $cityInfo->hostelCount > 1 && $cityInfo->topRatedHostel == $listing->id);
?>

@if ($isShowCheapestHostel || $isShowBoutiqueHostel || $isShowTopRated)
    <div class="mb-3 mb-lg-5 border-bottom pb-3 pb-lg-5 no-last-mb">
        @if ($isShowCheapestHostel)
            <a href="#availability" class="mb-3 d-flex align-items-center" data-smooth-scroll="">
                <span class="mr-4">@include('partials.svg-icon', ['svg_id' => 'boutique-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                {{ langGet('listingDisplay.CheapestHostel', ['city' => $cityData->city]) }}
            </a>
        @endif

        {{-- boutiqueHostel --}}
        @if ($isShowBoutiqueHostel)
            <p class="mb-3 d-flex align-items-center">
                <span class="mr-4">@include('partials.svg-icon', ['svg_id' => 'boutique-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                {{ langGet('listingDisplay.BoutiqueHostelFeature', ['city' => $cityData->city]) }}
            </p>
        @endif

        @if ($isShowTopRated)
            {{--Hostelz.com Ratings--}}
            @if ($ratings)
                <a href="#hostelzratings" class="mb-3 d-flex" data-smooth-scroll="">
                    <span class="mr-4">@include('partials.svg-icon', ['svg_id' => 'top-rated-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                    {{ langGet('listingDisplay.TopRatedHostel', ['city' => $cityData->city]) }}
                </a>
            @else
                <p class="mb-3 d-flex align-items-center">
                    <span class="mr-4">@include('partials.svg-icon', ['svg_id' => 'top-rated-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                    {{ langGet('listingDisplay.TopRatedHostel', ['city' => $cityData->city]) }}
                </p>
            @endif
        @endif
    </div>
@endif