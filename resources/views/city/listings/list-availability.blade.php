<?php

use App\Services\ImportSystems\ImportSystems;

?>

<div class="align-items-center d-md-flex flex-row">
    @if (!empty($bestAvailabilityByListingID))
        @php
            $availability = $bestAvailabilityByListingID[$listing->id];
            $availabilityByOTA = $bestAvailabilityByOTA[$listing->id];
            $savingsPercent = $availabilitySavingsPercent[$listing->id];
        @endphp

        @foreach ($availabilityByOTA as $system => $otaAvailability)
            @php
                $isHostelworldBestEquelPrice =
                    $loop->first &&
                    count($availabilityByOTA) > 1 &&
                    $system === 'BookHostels' &&
                    next($availabilityByOTA)->averagePricePerBlockPerNight(true) === $otaAvailability->averagePricePerBlockPerNight(true) &&
                    prev($availabilityByOTA);
                $isBestPrice = ($savingsPercent && $savingsPercent['system'] === $system) || $isHostelworldBestEquelPrice;
            @endphp
            <div class="mb-2 mb-md-0 mr-md-2">
                <a href="{!! $otaAvailability->bookingPageLink() !!}"
                   @class([
                        'mb-2 align-items-center d-flex flex-row mr-sm-0 p-2-1 rounded-sm text-decoration-none btn-system',
                        'bg-gray-200' => $isBestPrice,
                        'bg-accent' => !$isBestPrice
                    ])
                   title="{{ ImportSystems::findByName($system)->displayName }}" target="_blank"
                   onclick="ga('send','event','City','{{ ImportSystems::findByName($system)->displayName }}', '{!! $listing->city !!}')"
                   rel="nofollow"
                >

                    @include('partials.svg-icon', ['svg_id' => strtolower($system) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])

                    <span class="cl-subtext ml-2 mr-1 tx-small">@langGet('bookingProcess.from')</span>

                    <span class="cl-text font-weight-600 mr-3">
                        {{ $otaAvailability->averagePricePerBlockPerNight(true) }}
                    </span>

                    @if(
                        $savingsPercent &&
                        $savingsPercent['system'] === $system &&
                        $savingsPercent['percent'] > 2
                    )
                        <span class="align-items-center btn btn-danger d-flex ml-auto ml-md-0 rounded-sm">
                            <span class="tx-small text-lowercase mr-1">@langGet('global.Save')</span>
                            <span class="font-weight-600">{!! $savingsPercent['percent']!!}%</span>
                        </span>
                    @elseif($isHostelworldBestEquelPrice)
                        <span class="align-items-center btn btn-danger d-flex ml-auto ml-md-0 rounded-sm">
                            <span class="tx-small text-lowercase">@langGet('bookingProcess.BookNow')</span>
                        </span>
                    @endif
                </a>

                @if (!$otaAvailability->meetsMinimumNightsRequirement())
                    <div class="flex-center alert alert-warning">
                        <i class="cl-text fa fa-exclamation-triangle fa-fw mr-2"></i>
                        <span class="small">
                            {{ langGet('bookingProcess.MinNights', ['numNights' => $otaAvailability->minimumNightsRequired]) }}
                        </span>
                    </div>
                @endif
            </div>
        @endforeach

        @if (!$availability->hasFullAvailability())
            <div class="flex-center flex-column flex-sm-row align-items-start align-items-sm-center mr-auto mb-lg-0 mb-2">
                <div class="text-danger">
                    <i class="fa fa-exclamation-circle fa-fw"></i>&nbsp; @langGet('bookingProcess.OnlyPartialAvail')
                </div>
            </div>
        @endif
    @else
        <div class="flex-center flex-column flex-sm-row align-items-start align-items-sm-center mr-auto mb-lg-0 mb-2">
            <a href="#" title="" class="js-open-search-dates">
                <span class="tx-small">@langGet('city.ChooseBookingDates')</span>
            </a>
        </div>
    @endif
</div>
