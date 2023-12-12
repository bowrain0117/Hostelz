<div class="mainMap">
    @if ($resultsOptions['mapMode'] !== 'closed')
        <div class="mapHeader">
            <a href="#" class="setMapMode" data-map-mode="closed"><i class="fa fa-times"></i></a>
        </div>
        <div id="mapCanvas" class="mapBig mb-3"></div>
        <div class="d-flex justify-content-center align-items-center">
            <div class="d-flex justify-content-center align-items-center">
                <i class="fa fas fa-map-marker-alt fa-fw" style="color: #ff635c;"></i>
                <div>Hostels</div>
            </div>
            <div class="mr-2 ml-2">|</div>
            <div class="d-flex justify-content-center align-items-center">
                <i class="fa fas fa-map-marker-alt fa-fw" style="color: #004369;"></i>
                <div>Other Accommodations</div>
            </div>
        </div>
    @endif
</div>