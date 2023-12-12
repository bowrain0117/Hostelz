{{-- ** Special Note ** --}}
        
@if ($listing->specialNote != '')
    <div class="alert alert-warning">
        <h3>@langGet('listingDisplay.SpecialNote')</h3>
        <p>{!! $listing->specialNote !!}</p>
    </div>
@else
    <h2 class="hero-heading h3">{{ __('listingDisplay.sorryClosed') }}</h2>
@endif 

@if ($cityInfo && $cityInfo->totalListingCount)
    <h4>@langGet('listingDisplay.ButWaitHostels', [ 'city' => $cityInfo->translation()->city ])</h4>
    <p class="text-center mt-4">
        <a
            title="@langGet('bookingProcess.SearchAllOfCity', [ 'city' => $cityInfo->translation()->city ])"
            class="btn btn-lg btn-outline-primary bg-primary tt-n py-2 px-sm-5 font-weight-600 rounded text-white"
            href="{!! $cityInfo->getURL() !!}"
            onClick="javascript:turnDoBookingSearchForCityTo({!! $cityInfo->id !!}, true);return true;"
        >
            {{ langGet('bookingProcess.ComparePrices', [ 'city' => $cityInfo->translation()->city ]) }}
        </a>
    </p>
@else
    <div class="card shadow border-0 h-100 p-3 text-center justify-content-center">
        <p class="bold">{{ __('listingDisplay.closedHostelAlternatives', ['city' => $listing->city]) }}</p>

        <div class="row no-availability-option-box">
            <div class="col-md-6 mb-1">
                <p class="mt-0 font-weight-normal text-sm">Check Hostelworld</p>
                <p>
                    <a class="btn" target="_blank" rel="nofollow" href="https://www.hostelz.com/hwNA" title="Hostelworld" onclick="ga('send','event','No Availability','1st option: Hostelworld.com','')">Hostelworld.com</a>
                </p>
            </div>
            <div class="col-md-6 mb-1">
                <p class="mt-0 font-weight-normal text-sm">@langGet('city.emptyHostelsSuggestionsOptionText4a')</p>
                <p>
                    <a href='https://www.hostelz.com/airbnbreservationNA' class="btn" target="_blank" rel="nofollow" title="Airbnb" onclick="ga('send','event','No Availability','4th option: Airbnb.com','from {!! $listing->city !!}')">Airbnb.com</a>
                </p>
            </div>
            <div class="col-md-6 mb-1">
                <p class="mt-0 font-weight-normal text-sm">@langGet('city.emptyHostelsSuggestionsOptionText4b')</p>
                <p>
                    <a href='https://www.hostelz.com/vrboNA' class="btn" target="_blank" rel="nofollow" title="VRBO" onclick="ga('send','event','No Availability','4th option: VRBO.com','from {!! $listing->city !!}')">Vrbo.com</a>
                </p>
            </div>
            <div class="col-md-6 mb-1">
                <p class="mt-0 font-weight-normal text-sm">@langGet('city.emptyHostelsSuggestionsOptionText4c')</p>
                <p>
                    <a href='https://www.hostelz.com/bookingcomNA' class="btn" target="_blank" rel="nofollow" title="Booking.com" onclick="ga('send','event','No Availability','4th option: Booking.com','from {!! $listing->city !!}')">Booking.com</a>
                </p>
            </div>
        </div>
    </div>
@endif 
