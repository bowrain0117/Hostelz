<section class="city-hero py-5 py-lg-6 mb-3 mb-lg-5" >

    @if($heroImage = $cityInfo->heroImage)
        <div class="city-hero-img dark-overlay-before dark-overlay" style="background: #000000 url('{{ $heroImage }}') no-repeat center; background-size: cover;"></div>
    @else
        <div class="city-hero-img dark-overlay-before" style="background: #004369;"></div>
    @endif 

    <div class="container position-relative">
        <ul class="breadcrumb" vocab="http://schema.org/" typeof="BreadcrumbList">
            @breadcrumb(langGet('global.Home'), routeURL('home'))

            @if($cityInfo->continent) 
                @breadcrumb($cityInfo->translation()->continent, $cityInfo->getContinentURL(), 'hidden-xs')
            @endif

            @breadcrumb($cityInfo->translation()->country, $cityInfo->getCountryURL())

            @if ($cityInfo->displaysRegion && $cityInfo->translation()->region !== '')
                @breadcrumb($cityInfo->translation()->region, $cityInfo->getRegionURL())
            @endif

            @breadcrumb($cityInfo->translation()->city)
        </ul>

        <div class="title-section">
            <x-city.hero-title :$metaValues :$pageType />
        </div>
    </div>
</section>