{{--

Input variables:
    $errorMessage
    $cityInfo
    $listingsGroupedByPropertyType
--}}

@if (!empty($errorMessage))

    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {{{ $errorMessage }}}</div>

@elseif (!$listingsGroupedByPropertyType)

    @if (isset($bestAvailabilityByListingID))

        @include('city.cityControls')

        @include('city.noAvailability', compact('searchCriteria', 'cityInfo'))

    @else

        @include('city.cityControls')

        <div class="alert alert-warning">
            @langGet('city.SorryNoListings')
        </div>

    @endif

@else

    @if (
        !isset($bestAvailabilityByListingID) &&
        (!empty($listingFilters['typeOfDormRoom']) || isset($listingFilters['typeOfPrivateRoom']))
    )
        <div class="alert alert-danger">@langGet('city.AvailSearchNeededForRoomTypeFilter')</div>
    @endif

    @if ($mapPoints)
        @include('city.map', compact('resultsOptions'))
    @endif

    @include('city.availability')

    @stack('schema-scripts')

    <div class="d-none js-title-data">
        <x-city.hero-title :$metaValues :$pageType/>
    </div>

    <script type="text/javascript">
        @if ($mapPoints && $resultsOptions['mapMode'] !== 'closed')
        function doAfterMapScriptIsLoaded() {
            updateMapMarkers({!! $cityInfo->id !!}, {!! $mapBounds->json() !!}, {!! json_encode($mapPoints) !!});
            @if (!empty($selectedPoiInfo))
            addPoiMarker({!! $selectedPoiInfo['latitude'] !!}, {!! $selectedPoiInfo['longitude'] !!}, {!! json_encode($selectedPoiInfo['name']) !!});
            @endif
        }
        @endif
    </script>

@endif
