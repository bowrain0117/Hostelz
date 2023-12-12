@if (!empty($selectedPoiInfo))
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between my-3 mb-md-4">

        <h3 class="font-weight-600 mb-md-4 h4">
            @langGet('city.HostelsInCityNear', ['city'=> $cityInfo->translation()->city, 'sightname' => $selectedPoiInfo['name']])
        </h3>

        <span class="flex-center py-2 px-2 px-md-3 bg-gray-100 rounded">
            <img src="{!! routeURL('images', 'pin.svg') !!}" alt="#" class="mr-2">
            <span class="font-weight-600 display-4">
                @langGet('city.DistanceFromSight', ['city'=> $cityInfo->translation()->city, 'sightname' => $selectedPoiInfo['name']])
            </span>
        </span>
    </div>

@elseif (!isset($bestAvailabilityByListingID) && $resultsOptions['orderBy'] === 'price')
    <div class="alert alert-warning">@langGet('city.AvailSearchNeededForPrice')</div>
@endif

<div class="listingsList" id="vue-listings-card-slider"
     x-data="{}"
     x-init="$nextTick(()=> $dispatch('hostelz:updateListingsSearchResultContent'))"
>
    @if (count($listingsGroupedByPropertyType->toArray()) > 0)
        @foreach ($listingsGroupedByPropertyType as $propertyType => $listings)
            <div class="mb-lg-3 mb-3">
                <h2 class="title-3 cl-text">@langGet("global.propertyTypePlural.$propertyType")
                    in {{$cityInfo->translation()->city}}</h2>
            </div>

            @if($loop->index === 0)
                @include('city.cityControls')
            @endif

            @include ('city.listings.list', $listings)

        @endforeach

        @isset($paginationHTML)
            {!! $paginationHTML !!}
        @endisset
    @else
        <div>
            @langGet('city.EmptyCityData')
        </div>
    @endif
</div>

<script src="{{ mix('js/vue/modules/listings-card-slider.js')}}"></script>