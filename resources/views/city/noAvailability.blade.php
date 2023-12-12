<?php

use Lib\GeoMath;
use App\Models\CityInfo;

?>

@if ($searchCriteria->roomType === 'dorm')
    <p class="mb-3">{!! langGet('city.emptyHostelsSuggestionsTitleNoBeds', ['city' => $cityInfo->translation()->city]) !!}</p>
@else
    <p class="mb-3">{!! langGet('city.emptyHostelsSuggestionsTitleNoPrivateRoom', ['city' => $cityInfo->translation()->city]) !!}</p>
@endif

<div class="row no-availability-option-parent">
    @for ($x = 1; $x <= 4; $x++)
        <div class="col-lg-3 col-sm-6 mb-4 hover-animate no-availability-option-box">
            <div class="card shadow border-0 h-100 p-3 text-center justify-content-center" style="z-index:100">
                @if($x === 1)
                    @if ($searchCriteria->roomType === 'dorm')
                        <div class="card-title font-weight-bold">{{$x}}
                            . @langGet('city.emptyHostelsSuggestionsNoBedsOption1')</div>
                        <p class="card-body"><a href="#"
                                                class="disableHoverUnderline switchToPrivateSearch text-decoration-none">@langGet('bookingProcess.NoDormAvailabilityCity')</a>
                        </p>
                    @else
                        <div class="card-title font-weight-bold">{{$x}}
                            . @langGet('city.emptyHostelsSuggestionsNoRoomOption1')</div>
                        <p class="card-body"><a href="#"
                                                class="disableHoverUnderline switchToDormSearch text-decoration-none">@langGet('bookingProcess.NoPrivateAvailabilityCity')</a>
                        </p>
                    @endif
                @elseif($x == 2)
                    <div class="card-title font-weight-bold">{{$x}}
                        . @langGet('city.emptyHostelsSuggestionsOption2')</div>
                    <div class="card-body">
                        <a class="card-title no-available-another-date text-primary"><span
                                    class="mr-1">@include('partials.svg-icon', ['svg_id' => 'calendar', 'svg_w' => '24', 'svg_h' => '24'])</span>@langGet('city.emptyHostelsSuggestionsOption2')
                        </a>
                    </div>
                @elseif($x === 3)
                    @if ($cityInfo->nearbyCities)
                        <div class="card-title font-weight-bold">{{$x}}
                            . @langGet('city.emptyHostelsSuggestionsOption3')</div>
                        <div class="card-body flex-column text-left">
                            @foreach ($cityInfo->nearbyCities as $nearbyCity)
                                    <?php $otherCity = CityInfo::find($nearbyCity['cityID']); ?>
                                @if ($otherCity)
                                    <p class="mb-2">
                                        <a href="{!! $otherCity->getURL() !!}" class=""
                                           title="Hostels in {{{ $otherCity->translation()->city }}}">{{{ $otherCity->translation()->city }}}</a><span
                                                class="ml-1 text-xs">({!! round($nearbyCity['km'], $nearbyCity['km'] < 1) !!} Km / {!! GeoMath::kmToMiles($nearbyCity['km'], true) !!} mi.)</span>
                                    </p>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @elseif($x === 4)
                    <div class="card-title font-weight-bold">{{$x}}
                        . @langGet('city.emptyHostelsSuggestionsOptionTitle4')</div>

                    <div class="card-body flex-column">
                        <p class="mt-0 font-weight-normal text-sm">Check Hostelworld</p>
                        <p>
                            <a class="btn" target="_blank" rel="nofollow" href="https://www.hostelz.com/hwNA"
                               title="Hostelworld"
                               onclick="ga('send','event','No Availability','1st option: Hostelworld.com','')">Hostelworld.com</a>
                        </p>
                        <p class="mt-0 font-weight-normal text-sm">@langGet('city.emptyHostelsSuggestionsOptionText4a')</p>
                        <p>
                            <a href='https://www.hostelz.com/airbnbreservationNA' class="btn" target="_blank"
                               rel="nofollow" title="Airbnb"
                               onclick="ga('send','event','No Availability','4th option: Airbnb.com','from {!! $cityInfo->city !!}')">Airbnb.com</a>
                        </p>
                        <p class="mt-0 font-weight-normal text-sm">@langGet('city.emptyHostelsSuggestionsOptionText4b')</p>
                        <p>
                            <a href='https://www.hostelz.com/vrboNA' class="btn" target="_blank" rel="nofollow"
                               title="VRBO"
                               onclick="ga('send','event','No Availability','4th option: VRBO.com','from {!! $cityInfo->city !!}')">Vrbo.com</a>
                        </p>
                        <p class="mt-0 font-weight-normal text-sm">@langGet('city.emptyHostelsSuggestionsOptionText4c')</p>
                        <p>
                            <a href='https://www.hostelz.com/bookingcomNA' class="btn" target="_blank"
                               rel="nofollow" title="Booking.com"
                               onclick="ga('send','event','No Availability','4th option: Booking.com','from {!! $cityInfo->city !!}')">Booking.com</a>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endfor
</div>