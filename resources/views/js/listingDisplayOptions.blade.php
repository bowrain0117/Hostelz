<?php

use App\Models\Languages;

$listingOptions = [
    "domainName" => config('custom.domainName'),
    "locationName" => $listing->name,
    "listingBookingSearchURL" => @routeURL('listingBookingSearch', [ '' ]),
    "listingDynamicDataURL" => @routeURL('listingDynamicData', [ '' ]),
    "googleApiKey" => urlencode(config('custom.googleApiKey.clientSide')),
    "language" => Languages::current()->otherCodeStandard('IANA'),
    "moreHostelsURL" => routeURL('getMoreHostels', [ $listing->id ]),
    "mapMarker" => [
        'mapMarkerCities' => routeURL('images', 'mapMarker-poi-n.png'),
        'mapMarkerCitiesHighlighted' => routeURL('images', 'mapMarker-hostel-highlighted-n.png'),
        'width' => \App\Helpers\ListingDisplay::LISTING_MAP_MARKER_WIDTH,
        'height' => \App\Helpers\ListingDisplay::LISTING_MAP_MARKER_HEIGHT,
    ]
];
?>
<script type="text/javascript">
    let optionsStringify = JSON.stringify({!! json_encode($listingOptions) !!})

    let listingOptions = JSON.parse(optionsStringify);
</script>
