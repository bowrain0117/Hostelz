<?php

use App\Models\CityInfo;
use App\Services\Listings\ListingsOptionsService;
use Illuminate\Support\Js;

$globalOptions = [
    'routes' => [
        'searchAutocomplete' => routeURL('searchAutocomplete'),
        'userSearch' => routeURL('user:searchHistory'),
        'cityMarkerPoints' => routeURL('cityMarkerPoints'),
        'mapMarkerCityMuted' => routeURL('images', 'mapMarker-city-muted.png'),
        'mapMarkerCity' => routeURL('images', 'mapMarker-city-n.png'),
        'mapMarkerCityHighlighted' => routeURL('images', 'mapMarker-city-highlighted-n.png'),
    ],
    'config' => [
        'originationReferrerCookie' => config('custom.originationReferrerCookie'),
        'domainName' => config('custom.domainName'),
        'affiliateIdCookie' => config('custom.affiliateIdCookie'),
        'searchCriteriaCookie' => config('custom.citySearchCriteriaCookie'),
    ],
    'cityMapMarkerWidth' => CityInfo::CITY_MAP_MARKER_WIDTH * 0.5,
    'cityMapMarkerHeight' => CityInfo::CITY_MAP_MARKER_HEIGHT * 0.5,
    'sections' => [
        'generalLoadFailedErrorMessage' => "<div class='alert alert-danger'><i class='fa fa-exclamation-circle'></i>&nbsp; " . langGet('global.generalLoadFailedErrorMessage') . "</div>",
    ],
    'defaultListingsShowOptions' => ListingsOptionsService::getDefaultListingsShowOptions(),
];
?>

<script>
    var globalOptions = {{ Js::from($globalOptions) }}
</script>
