<h3 id="mapHostels">On the map: Hostel Locations in {{ $slp->subjectable->city }}</h3>
<p>We've got your back when it comes to finding a great hostel location in {{ $slp->subjectable->city }}. We've gone
    ahead and mapped out all the hostels for you so you can get a good idea of where they're all at. This map will
    make your life so much easier!</p>
<div class="card text-dark overflow-hidden border-0 rounded-0 mb-3" id="location">
    <div class="map-wrapper map-wrapper-300">
        <div id="mapCanvas" class="h-100"></div>
        <div class="map-overlay position-relative">
            <img src="{!! routeURL('images', 'hostel-map.jpg') !!}" alt="slp name" class="card-img map-wrapper-300">
            <div class="card-img-overlay-center text-center">
                <div class="overlay-content">
                    <button type="button" class="card-text btn bg-white tt-n btn-load-map"><i
                                class="fa fas fa-map-marker-alt fa-fw mr-2"></i> @langGet('listingDisplay.LoadMap')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@section('pageBottom')

    <a id="mapFloat" class="toogleOnScroll" href="#mapHostels">
        <img src="{!! routeURL('images', 'map-float.jpg') !!}" alt="">
    </a>

    @parent

    <script type="text/javascript">
        const citiesOptions = {{ Illuminate\Support\Js::from([
            'mapMarker' => [
                'mapMarkerBlue' => routeURL('images', 'mapMarker-red.png'),
                'mapMarkerBlueHostelHighlighted' => routeURL('images', 'mapMarker-red-highlighted.png'),
                'width' => \App\Models\CityInfo::CITY_MAP_MARKER_WIDTH,
                'height' => \App\Models\CityInfo::CITY_MAP_MARKER_HEIGHT,
                ],
        ]) }};

        function initMap() {
            displayMap({!! $pois->mapBounds->json() !!}, {!! json_encode($pois->mapPoints) !!});
        }

        $(document).ready(function () {
            $('.btn-load-map').click(function () {

                $('.map-overlay').fadeOut();
                $('.map-wrapper #mapCanvas').fadeIn();

                $.getScript("//maps.googleapis.com/maps/api/js?v=3&key={!! urlencode(config('custom.googleApiKey.clientSide')) !!}&callback=initMap")
            });
        })

        function displayMap(mapBounds, mapPoints) {
            const map = createMap('mapCanvas', mapBounds);
            // * Marker Icons *
            const hostelIcon = {
                url: citiesOptions.mapMarker.mapMarkerBlue,
                size: new google.maps.Size(citiesOptions.mapMarker.width * 0.5, citiesOptions.mapMarker.height * 0.5),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(citiesOptions.mapMarker.width * 0.5, citiesOptions.mapMarker.height)
            };

            const hostelIconHighlighted = $.extend({}, hostelIcon, {url: citiesOptions.mapMarker.mapMarkerBlueHostelHighlighted});

            scaleIconToZoomLevel(map, hostelIcon, 19, 4, 0.5, 1.0);
            scaleIconToZoomLevel(map, hostelIconHighlighted, 19, 4, 0.5, 1.0);

            // * Markers / InfoWindow *

            const infoWindow = new google.maps.InfoWindow();

            $.each(mapPoints, function (i, mapPoint) {
                const marker = new google.maps.Marker({
                    position: new google.maps.LatLng(mapPoint.lat, mapPoint.long),
                    anchorPoint: new google.maps.Point(0, -(hostelIcon.scaledSize.height / 2)),
                    zIndex: google.maps.Marker.MAX_ZINDEX + 1,
                    map: map,
                    icon: hostelIcon,
                    title: mapPoint.name
                });

                marker.addListener('click', function () {
                    infoWindow.setContent(mapPoint.markerInfo);
                    infoWindow.open(map, marker);
                });

                google.maps.event.addListener(marker, 'mouseover', function () {
                    marker.setIcon(hostelIconHighlighted);
                });
                google.maps.event.addListener(marker, 'mouseout', function () {
                    marker.setIcon(hostelIcon);
                });
            });
        }

    </script>

@stop
