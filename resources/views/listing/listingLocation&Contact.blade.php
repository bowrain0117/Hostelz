<div class="border-bottom mb-5 pb-5 text-break" id="listing-location-contact">

    <h3 class="sb-title cl-text mb-5" id="contact">{!! langGet('listingDisplay.LocationContact') !!}</h3>

    @if (! empty($location))
        <h4 class="title-4 font-weight-600 cl-text mb-2">{!! langGet('listingDisplay.Location') !!}</h4>
        <div class="text-content">
            <p>
                {!! paragraphsAsBullets($location) !!}
            </p>
        </div>
    @endif

    {{--Map New Image--}}
    @if ($listing->hasLatitudeAndLongitude() && !in_array('isClosed', $listingViewOptions))
        <h4 class="title-4 font-weight-600 cl-text mb-2" id="location">@langGet('listingDisplay.Map', ['hostelName' => $listing->name])</h4>

        <div class="card text-dark overflow-hidden border-0 rounded-0 mb-3" id="location">
            <div class="map-wrapper map-wrapper-300">
                <div id="mapCanvas" class="h-100"></div>
                <div class="map-overlay position-relative">
                    <img src="{!! routeURL('images', 'map-hostel-location.jpg') !!}" alt="@langGet('listingDisplay.Map', ['hostelName' => $listing->name])" class="card-img map-wrapper-300">
                    <div class="card-img-overlay-center text-center">
                        <div class="overlay-content">
                            <button type="button" class="card-text btn bg-white tt-n btn-load-map"><i class="fa fas fa-map-marker-alt fa-fw mr-2"></i> @langGet('listingDisplay.LoadMap')</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (!in_array('isClosed', $listingViewOptions))
        <p class="mb-3">
            <span class="font-weight-600 cl-text">@langGet('listingDisplay.Address')</span>:
            <a href="https://maps.google.com/?q={{ $listing->latitude }},{{ $listing->longitude }}" target="_blank" rel="nofollow">
                @if ($listing->address != '')<span property="streetAddress">{{{ $listing->address }}}</span>, @endif
                @if ($listing->cityAlt != ''){{{ $listing->cityAlt }}}, @endif
                @if ($cityInfo)
                    <span property="addressLocality">{{{ $cityInfo->translation()->city }}}</span>@if ($cityInfo->translation()->cityAlt) ({{{ $cityInfo->translation()->cityAlt }}})@endif,
                    @if ($cityInfo->translation()->region != '') <span property="addressRegion">{{{ $cityInfo->translation()->region }}}</span>, @endif
                    <span property="addressCountry" content="{{{ $cityInfo->countryInfo ? $cityInfo->countryInfo->countryCode() : $cityInfo->translation()->country }}}">{{{ $cityInfo->translation()->country }}}</span>
                @else
                    <span property="addressLocality">{{{ $listing->city }}}</span>,
                    @if ($listing->region != '') <span property="addressRegion">{{{ $listing->region }}}</span>, @endif
                    <span property="addressCountry">{{{ $listing->country }}}</span>
                @endif
                @if ($listing->zipcode != '')
                    <meta property="postalCode" content="{{{ $listing->zipcode }}}">
                @endif
            </a>
        </p>

{{--    @dump($listing->distanceToCityCenter)--}}

        @if($listing->distanceToCityCenter)
            <p class="mb-3">
                <span class="font-weight-600 cl-text">{{ langGet('listingDisplay.DistanceToCityCenter') }}:</span>
                {{ $listing->distanceToCityCenter }}
                <span>km</span>
            </p>
        @endif

        {{-- listed on the following booking sites --}}
        <h3 class="title-3 cl-dark mb-3">{!! langGet('listingDisplay.ListedAt', [ 'hostelName' => $listing->name]) !!}:</h3>

        @foreach($contactActiveImports as $item)
            @if($item['isListed'])
                <a
                    href="{!! $item['href'] !!}"
                    onclick="ga('send','event','single','listed','{{{ $listing->name }}}, {!! $listing->city !!} - {!! $item['systemShortName'] !!}')"
                    title="{{{ $listing->name }}} {!! langGet('listingDisplay.At') !!} {!! $item['systemShortName'] !!}" target="_blank"
                    class="btn btn-light rounded mb-3 w-100 d-flex align-items-center justify-content-center"
                    rel="nofollow"
                >
                    @include('partials.svg-icon', ['svg_id' => strtolower($item['systemName']) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
                    <span class="overflow ml-2 d-inline-block">{!! $item['systemShortName'] !!}</span>
                </a>
            @else
                <a
                    href="{!! $item['href'] !!}"
                    onclick="ga('send','event','single','not listed','{!! $listing->name !!}, {!! $listing->city !!} - {!! $item['systemShortName'] !!}')"
                    title="{!! langGet('listingDisplay.NotListedAt', [ 'hostelName' => $listing->name]) !!} {!! $item['systemShortName'] !!}" target="_blank"
                    class="btn btn-light rounded mb-3 w-100 d-flex align-items-center justify-content-center text-lowercase opacity-5"
                    rel="nofollow"
                >
                    @include('partials.svg-icon', ['svg_id' => strtolower($item['systemName']) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
                    <span class="overflow ml-2 d-inline-block font-weight-normal">{!! langGet('listingDisplay.NotListedAt', [ 'hostelName' => $listing->name]) !!} {!! $item['systemShortName'] !!}</span>
                </a>
            @endif
        @endforeach

        @if($listing->hostelsChain)
            <h4 class="title-4 font-weight-600 cl-text mb-3">{{ langGet('HostelsChain.listingPartOf', [ 'hostelName' =>
                $listing->name, 'hostelsChainName' => $listing->hostelsChain->name ]) }}</h4>
            <a href="{{ $listing->hostelsChain->path }}" title="{{ $listing->hostelsChain->name }}" target="_blank" class="btn btn-light rounded mb-3 w-100">
                <i class="fas fa-code-branch mr-2"></i> <span class="overflow">{{ langGet('HostelsChain.findAll', [ 'hostelsChainName' => $listing->hostelsChain->name ]) }}</span>
            </a>
        @endif

        

        @if ($listing->webDisplay >= 0 && ($listing->web != '' || $listing->webStatus <= 0 || $listing->onlineReservations != 1 || $listing->isPrimaryPropertyType() || $listing->mgmtBacklink != ''))
            <h4 class="title-4 font-weight-600 cl-text mb-3">{!! langGet('listingDisplay.Website') !!}</h4>

            @if ($listing->web != '' && $listing->webStatus >= 0 && $listing->webDisplay >= 0)
                @if ($listing->isLive())
                    <a href="{!! routeURL('listing-website', $listing->id) !!}" rel="nofollow" onclick="ga('send','event','Contact','Official Website','{{{ $listing->name }}}, {!! $listing->city !!}')" title="{{{ $listing->name }}} in {!! $listing->city !!}" target="_blank" class="btn btn-light rounded mb-3 w-100">
                        <img src="{!! routeURL('images', 'red-globe.svg') !!}" alt="{!! langGet('listingDisplay.Website') !!} {{{ $listing->name }}} {!! $listing->city !!}" class="mr-3"> <span class="overflow">{{{ str_replace([ 'http://', 'https://' ], '', $listing->web) }}}</span>
                    </a>
                @else
                    {{-- The listing isn't live, so the listing-website page won't allow it, so we just link to the website directly. --}}
                    <a href="{!! $listing->web !!}" rel="nofollow" target="_blank" onclick="ga('send','event','Contact','Official Website','{{{ $listing->name }}}, {!! $listing->city !!}')" title="{{{ $listing->name }}} {!! $listing->city !!}" class="btn btn-light rounded mb-3 w-100">
                        <img src="{!! routeURL('images', 'red-globe.svg') !!}" alt="{!! langGet('listingDisplay.Website') !!} {{{ $listing->name }}} {!! $listing->city !!}" class="mr-3"><span class="overflow">{{{ str_replace([ 'http://', 'https://' ], '', $listing->web) }}}</span>
                    </a>
                @endif
            @else
                {{--{!! langGet('listingDisplay.None') !!}--}}
                <span class="btn btn-light rounded mb-3 w-100">
                    <img src="{!! routeURL('images', 'red-globe.svg') !!}" alt="{!! langGet('listingDisplay.Website') !!} {{{ $listing->name }}} {!! $listing->city !!}" class="mr-3">
                    <span>{!! langGet('listingDisplay.IfYouKnowWeb', [ 'url' => routeURL('listingCorrection', $listing->id) ]) !!}</span>
                </span>
            @endif
        @endif

        <div class="cl-text my-3">
            <span class="title-4 cl-text font-weight-600">{!! langGet('listingDisplay.Telephone') !!}: </span>
            @if ($listing->tel)
                <span>{{{ $listing->tel }}}</span>
            @else
                <span>{!! langGet('listingDisplay.UnknownPhone', [ 'url' => routeURL('listingCorrection', $listing->id) ]) !!}</span>
            @endif
        </div>

        @if ($listing->supportEmail && !$listing->onlineReservations && $listing->web == '')
            <div class="mb-3">
                <span class="title-4 cl-text font-weight-600">{!! langGet('listingDisplay.Email') !!}: </span>
                <span class="mb-4"><a href="mailto:{{{ implode(', ', $listing->supportEmail) }}}?subject={{{ $listing->name }}} (from Hostelz.com)" onclick="ga('send','event','Contact','Official Email','{{{ $listing->name }}}, {!! $listing->city !!}')" target="_blank">{{{ implode(', ', $listing->supportEmail) }}}</a></span>
            </div>
        @endif

        <div class="tx-small">
            {!! langGet('listingDisplay.UseTheListingUpdateForm', [ 'url' => routeURL('listingCorrection', $listing->id) ]) !!}
        </div>
    @endif
</div>