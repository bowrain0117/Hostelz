{{--@include('listing/listingBoutiqueFeature')--}}


{{-- Show the Closed Alert again --}}
@if (in_array('isClosed', $listingViewOptions))
    <div class="alert alert-danger text-center mb-4">
        @include('partials/_listingClosedAlert')
    </div>
@elseif ($cityInfo)

    @if ($listing->propertyType == 'Hostel' && $cityInfo->hostelCount > 2)
        <section class="container my-5 text-center">
            <p class="mb-3 font-weight-600">@langGet('listingDisplay.DidNotFindHostelYet')</p>
            <a href="{!! $cityInfo->getURL() !!}" title="@langGet('city.HostelsInCity', ['city' => $cityInfo->translation()->city])" class="cl-link">@langGet('listingDisplay.SeeAllHostelsBottom', ['city' => $cityInfo->translation()->city, 'count' => $cityInfo->hostelCount])</a>
        </section>
    @elseif ($cityInfo->totalListingCount > 2)
        <section class="container my-5 text-center">
            <p class="mb-3 font-weight-600">@langGet('listingDisplay.DidNotFindHostelYet')</p>
            <a href="{!! $cityInfo->getURL() !!}" title="@langGet('city.HostelsInCity', ['city' => $cityInfo->translation()->city])" class="cl-link">@langGet('listingDisplay.SeeAllListingsBottom', ['city' => $cityInfo->translation()->city, 'count' => $cityInfo->hostelCount])</a>
        </section>
    @else
        <section class="container my-5 text-center">
            <p class="mb-3 font-weight-600">@langGet('listingDisplay.DidNotFindHostelYet')</p>
            <a href="{!! $cityInfo->getURL() !!}" title="@langGet('city.HostelsInCity', ['city' => $cityInfo->translation()->city])" class="cl-link">@langGet('listingDisplay.SeeAllListingsBottomNoCount', ['city' => $cityInfo->translation()->city])</a>
        </section>
    @endif
@endif
