<div class="listing-city-info mb-lg-5 mb-3 border-bottom pb-lg-5 pb-3">
    <h2 class="sb-title cl-text mb-0 d-none d-lg-block" id="backpacking">{!! langGet('city.BackpackingTo', [ 'city' => $cityInfo->translation()->city]) !!}</h2>

    <p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed" data-toggle="collapse" href="#listing-city-info-content">
        {!! langGet('city.BackpackingTo', [ 'city' => $cityInfo->translation()->city]) !!}
        <i class="fas fa-angle-down float-right"></i>
        <i class="fas fa-angle-up float-right"></i>
    </p>
    <div class="text-content mt-3 collapse d-lg-block" id="listing-city-info-content">
        @if ($cityInfo->getLiveDescription())
            <p>{!! langGet('city.BasicTipsIntro', [ 'city' => $cityInfo->translation()->city]) !!}</p>
        @endif

        @if ($cityInfo->cityAlt != '')
            <div class="mb-3 mb-sm-4">{!! langGet('city.cityAltText', [ 'city' => $cityInfo->translation()->city, 'cityAlt' => $cityInfo->cityAlt]) !!}</div>
        @endif

        @if ($cityInfo->cityGroup != '')
            <div class="mb-3 mb-sm-4">{!! langGet('city.CityGroupInArea', [ 'city' => $cityInfo->translation()->city, 'cityGroup' => $cityInfo->cityGroup, 'cityGroupLink' => $cityInfo->getCityGroupURL()]) !!}</div>
        @endif

        @if ( $cityInfo->getLiveDescription() )
            @if ($cityPics)
                <img class="float-lg-left mb-3 mr-4 img-fluid" src="{!! $cityPics->url([ '' ]) !!}" alt="@if ($cityPics->caption) {{{ $cityPics->caption }}} @else {!! langGet('city.BackpackingTo', [ 'city' => $cityInfo->translation()->city]) !!} @endif" title="@if ($cityPics->caption) {{{ $cityPics->caption }}} @else {!! langGet('city.BackpackingTo', [ 'city' => $cityInfo->translation()->city]) !!} @endif">
            @endif

            {!! nl2p(trim($cityInfo->getLiveDescription()->data)) !!}

            @if ($cityInfo->getLiveDescription()->user && $cityInfo->getLiveDescription()->user->nickname != '')
                <div class="border-top pt-3">
                    <div class="">
                        <p class="pre-title mb-1">{!! langGet('city.LocalExpertTitle', [ 'city' => $cityInfo->translation()->city]) !!}</p>

                        @if ($cityInfo->getLiveDescription()->user->profilePhoto)
                            <img src="{!! $cityInfo->getLiveDescription()->user->profilePhoto->url([ 'thumbnails' ]) !!}" alt="{!! langGet('city.LocalExpertTitle', [ 'city' => $cityInfo->translation()->city]) !!}" title="{!! langGet('city.LocalExpertTitle', [ 'city' => $cityInfo->translation()->city]) !!}" class="avatar mr-2">
                        @else
                            <img src="{!! routeURL('images', 'hostelz-blogger-writer.jpg') !!}" alt="{!! langGet('city.LocalExpertTitle', [ 'city' => $cityInfo->translation()->city]) !!}" title="{!! langGet('city.LocalExpertTitle', [ 'city' => $cityInfo->translation()->city]) !!}" class="avatar mr-2">
                        @endif
                        <span class="cl-text font-weight-600">{{{ $cityInfo->getLiveDescription()->user->nickname }}}</span>
                    </div>
                </div>
            @endif

        @elseif( App\Models\Languages::currentCode() !== 'en' )
            <p>{!! langGet('city.EnglishCityInformation', [ 'cityLink' => $cityInfo->getURL('auto', 'en'), 'city' => $cityInfo->translation()->city ]) !!}</p>
        @elseif( App\Models\Languages::currentCode() === 'en' )
            <p>{!! langGet('city.NoCityInformation', [ 'cityLink' => $cityInfo->getURL('auto', 'en'), 'city' => $cityInfo->translation()->city ]) !!}</p>
        @endif
    </div>
</div>
