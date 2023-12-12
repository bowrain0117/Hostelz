@if($quickestAnswer = $cityInfo->getQuickestAnswer())
<div class="listingsList pt-3 mb-3" id="shortcut">
	<p class="font-weight-bold">{!! langGet('city.QuickAnswer') !!}</p>
	<div class="row">

		{{--	Best-Rated Hostel	--}}
		@if($topRated = $quickestAnswer['topRatedHostel'])
        <div data-marker-id="{!! $topRated->id !!}" class="col-lg-4 mb-5 hover-animate listing listingLink listing-gridFormat">
        	<div class="card h-100 border-0 shadow">
            	<div style="background-image: url({!! $topRated->thumbnailURL() !!}); min-height: 200px;" class="overflow-hidden bg-cover dark-overlay shadow-lg">
                  	<a href="{!! $topRated->getURL() !!}" class="tile-link" target="_blank" title="{!! langGet('city.BestRated') !!} {{{ $cityInfo->translation()->city }}}: {{{ $topRated->name }}}"></a>
                  	<div class="card-img-overlay-top z-index-20 d-flex justify-content-between align-items-center">
                  		<div class="bg-primary text-white display-4 p-2 font-weight-bolder mt-1 d-inline-block">
                        	<span class="listing-CombinedRating">{!! langGet('city.BestRated') !!}</span>
                    	</div>
                    </div>
                    <div class="card-img-overlay-bottom z-index-20">
                    	<p class="text-white text-shadow h4">{{ $topRated->name }}</p>
                    </div>
                </div>
            </div>
        </div>
		@endif

		{{--	Best for Solo-Traveller	--}}
		@if($bestSoloTraveller = $quickestAnswer['bestSoloTraveller'])
        <div data-marker-id="{!! $bestSoloTraveller->id !!}" class="col-lg-4 mb-5 hover-animate listing listingLink listing-gridFormat">
        	<div class="card h-100 border-0 shadow">
            	<div style="background-image: url({!! $bestSoloTraveller->thumbnailURL() !!}); min-height: 200px;" class="overflow-hidden bg-cover dark-overlay shadow-lg">
                  	<a href="{!! $bestSoloTraveller->getURL() !!}" class="tile-link" target="_blank" title="{!! langGet('city.BestSoloTraveller') !!} {{{ $cityInfo->translation()->city }}}: {{{ $bestSoloTraveller->name }}}"></a>
                  	<div class="card-img-overlay-top z-index-20 d-flex justify-content-between align-items-center">
                  		<div class="bg-primary text-white display-4 p-2 font-weight-bolder mt-1 d-inline-block">
                        	<span class="listing-CombinedRating">{!! langGet('city.BestSoloTraveller') !!}</span>
                    	</div>
                    </div>
                    <div class="card-img-overlay-bottom z-index-20">
                    	<p class="text-white text-shadow h4">{{ $bestSoloTraveller->name }}</p>
                    </div>
                </div>
            </div>
        </div>
		@endif

		{{--	CheapestHostel	--}}
		@if($cheapest = $quickestAnswer['cheapestHostel'])
			<div data-marker-id="{!! $cheapest->id !!}" class="col-lg-4 mb-5 hover-animate listing listingLink listing-gridFormat">
				<div class="card h-100 border-0 shadow">
					<div style="background-image: url({!! $cheapest->thumbnailURL() !!}); min-height: 200px;" class="overflow-hidden bg-cover dark-overlay shadow-lg">
						<a href="{!! $cheapest->getURL() !!}" class="tile-link" target="_blank" title="{!! langGet('city.CheapestHostel') !!} {{{ $cityInfo->translation()->city }}}: {{{ $cheapest->name }}}"></a>
						<div class="card-img-overlay-top z-index-20 d-flex justify-content-between align-items-center">
							<div class="bg-primary text-white display-4 p-2 font-weight-bolder mt-1 d-inline-block">
								<span class="listing-CombinedRating">{!! langGet('city.CheapestHostel') !!}</span>
							</div>
						</div>
						<div class="card-img-overlay-bottom z-index-20">
							<p class="text-white text-shadow h4">{{ $cheapest->name }}</p>
						</div>
					</div>
				</div>
			</div>
		@endif
        
	</div>
</div>
@endif