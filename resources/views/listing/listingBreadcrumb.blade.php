@if ($listing->isLive())
    <ul class="breadcrumb black px-0" vocab="http://schema.org/" typeof="BreadcrumbList">
        {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
        @if ($cityInfo)
            @if ($cityData->continent)
                {!! breadcrumb($cityData->continent, $cityInfo->getContinentURL(), 'hidden-xs') !!}
            @endif

            {!! breadcrumb($cityData->country, $cityInfo->getCountryURL()) !!}
            @if ($cityInfo->displaysRegion && $cityData->region != '')
                @if (!$cityInfo->getTotalListingsInRegion())
                    <li class="breadcrumb-item" property="itemListElement" typeof="ListItem">
                        <span class="breadcrumb-item-no-link" property="name">{{ $cityData->region }}</span>
                        <meta property="position" content="3">
                    </li>
                @else
                    {!! breadcrumb($cityData->region, $cityInfo->getRegionURL()) !!}
                @endif
            @endif
            @if ($cityInfo->totalListingCount)
                {!! breadcrumb($cityData->city, $cityInfo->getURL()) !!}
            @else
                <li class="breadcrumb-item" property="itemListElement" typeof="ListItem">
                    <span class="breadcrumb-item-no-link" property="name">{{ $cityData->city }}</span>
                    <meta property="position" content="3">
                </li>
            @endif
        @endif
        {!! breadcrumb($listing->name) !!}
    </ul>
@else
    <ul class="breadcrumb black px-0" vocab="http://schema.org/" typeof="BreadcrumbList">
        {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
        {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
        {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
        {!! breadcrumb('Listing Preview') !!}
    </ul>
@endif
