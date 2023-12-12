
{{--Chooses to make--}}
@if (!in_array('isClosed', $listingViewOptions))
    <div id="bookingSearchResult"></div>
@endif

{{-- ** Special Note ** --}}
@if ($listing->specialNote != '')
    <div class="alert alert-warning">
        <h2 class="h3">@langGet('listingDisplay.SpecialNote')</h2>
        <p>{!! $listing->specialNote !!}</p>
    </div>
@endif

{{-- Show the Closed Alert again --}}
@if (in_array('isClosed', $listingViewOptions))
    <div class="alert alert-danger text-center mb-4">
        @include('partials/_listingClosedAlert')
    </div>
@endif


{{-- "Maybe Closed" --}}
@if (!$listing->onlineReservations && $listing->specialNote == '')
    {{-- No active import system, and used to & no website listed, ?? the website is no longer up... --}}
    @if (!$listing->activeImporteds && (($listing->importeds && $listing->web == '') || $listing->webStatus < 0))
        <div class="alert alert-warning underlineLinks">
            <b>{!! langGet('listingDisplay.SpecialNote') !!}:</b> <i>
                @if ($listing->importeds)
                    @if ($listing->webStatus < 0)
                        {{{ $listing->name }}} is no longer listed in any of the hostel booking systems, and the website we had listed for it is no longer up.
                    @else
                        {{{ $listing->name }}} is no longer listed in any of the hostel booking systems.
                    @endif
                    &nbsp;There is a good chance they may have closed down.
                @else
                    {{-- Never was in any booking system, but the website is down, still not a good sign... --}}
                    The website we had listed for {{{ $listing->name }}} is no longer up.  They may have closed down.
                    {!! langGet('listingDisplay.IfYouKnowWeb', [ 'url' => routeURL('listingCorrection', $listing->id) ]) !!}
                @endif

                &nbsp;If you have any information about it, please let us know with the <a href="{{{ $LANG_URL_PREFIX }}}/listing-correction/{{{ $listing->id }}}">{!! langGet('listingDisplay.ListingUpdateForm') !!}</a>.

                @if ($cityInfo && $cityInfo->totalListingCount > 1)
                    <p>For alternative options in {{{ $cityInfo->translation()->city }}}, see the <a href="{{{ $cityURL }}}">{{{ $cityInfo->translation()->city }}} @if ($cityInfo->hostelCount || $listing->propertyType == 'Hostel'){!! langGet('default.Hostels') !!}@else {!! langGet('default.HotelsAndGuestHouses') !!}@endif </a> list.</p>
                @endif

            </i></div>
    @endif
@endif
