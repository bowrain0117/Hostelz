<section>
    <div class="container">
        <ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0" vocab="http://schema.org/" typeof="BreadcrumbList">
            @breadcrumb(langGet('global.Home'), routeURL('home'))
            @if($cityInfo->continent)
                @breadcrumb($cityInfo->translation()->continent, $cityInfo->getContinentURL(), 'hidden-xs')
            @endif
            @breadcrumb($cityInfo->translation()->country, $cityInfo->getCountryURL())
            @if ($cityInfo->displaysRegion && $cityInfo->translation()->region != '')
                @breadcrumb($cityInfo->translation()->region, $cityInfo->getRegionURL())
            @endif
            @breadcrumb($cityInfo->translation()->city)
        </ul>
        <div class="mb-lg-2 pb-md-2 mx-sm-n3 mx-lg-0">
            <h1 class="mb-2 mb-lg-2 h2" id="allhostels">{!! langGet('city.TopTitle', ['hostelCount' => $cityInfo->hostelCount, 'count' => $cityInfo->totalListingCount, 'city' => $cityInfo->translation()->city, 'area' => $cityInfo->translation()->country, 'year' => date("Y") ]) !!}</h1>
            <p class="">{!! langGet('city.TopText', ['lowestDormPrice' => $lowestDormPrice, 'hostelCount' => $cityInfo->hostelCount, 'count' => $cityInfo->totalListingCount, 'city' => $cityInfo->translation()->city, 'area' => $cityInfo->translation()->country, 'year' => date("Y") ]) !!}</p>
        </div>

       @if ($cityInfo->hostelCount >= 10)
            {{-- New Addons for City Pages --}}
            {{-- @include('city_addon_featured')--}}
        @endif

    </div>
</section>