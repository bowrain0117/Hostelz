<?php

use App\Models\Languages;
use App\Models\CityInfo;
use App\Helpers\ListingDisplay;
use App\Services\Listings\ListingsOptionsService;
use Illuminate\Support\Js;

?>

@push('scriptOptions')
	@php
		$cityOptions = [
			"cityId" =>  $cityInfo->id,
			"cityName" =>  $cityInfo->city,
			"cityURL" =>  $cityInfo->getURL('absolute'),
			"domainName" => config('custom.domainName'),
			"langCheckAvailability" =>  langGet('bookingProcess.CheckAvailability') . ':</span> ',
			"langSearchCriteriaStartDate" =>  langGet('bookingProcess.searchCriteria.startDate') . ': ' ,
			"langSearchCriteriaNights" =>  langGet('bookingProcess.searchCriteria.nights') . ': ',

			"staffCityInfos" => routeURL('staff-cityInfos'),
			"getCityAd" => routeURL('getCityAd', ''),
			"staticCityListingsListContent" => routeURL('staticCityListingsListContent', [ '', '', '' ]),
			"listingsListContent" => routeURL('listingsListContent', [ '' ]),
			"exploreURL" => routeURL('getExploreSection', [$cityInfo->id]),

			"mapMarker" => routeURL('images', 'mapMarker-red.png'),
			"mapMarkerBlue" => routeURL('images', 'mapMarker-blue.png'),
			"mapMarkerWidth" =>  ListingDisplay::LISTING_MAP_MARKER_WIDTH ,
			"mapMarkerHeight" =>  ListingDisplay::LISTING_MAP_MARKER_HEIGHT ,
			"scalePoiIcon" => 0.8,
			"mapMarkerHostelHighlighted" => routeURL('images', 'mapMarker-red-highlighted.png'),
			"mapMarkerBlueHostelHighlighted" => routeURL('images', 'mapMarker-blue-highlighted.png'),
			"searchCriteriaCookie" =>  config('custom.bookingSearchCriteriaCookie'),
			"searchData" =>  [
				"itemId" => $cityInfo->id,
				"query" => $cityInfo->city,
				"category" => 'city',
				"searchURL" => $cityInfo->getURL('absolute'),
				"districts" => $cityInfo->districts->map(function ($district) {
					return [
						"type" => 'district',
						"value" => $district->id,
						"title" => $district->name,
					];
				}),
			],
			'pageType' => $pageType,
			'orderBy' => $orderBy,
		];
	@endphp

	<script>
        var cityOptions = @json($cityOptions)
	</script>
@endpush

@section('pageBottom')

	@include('wishlist.modalWishlists')

	@include('wishlist.modalCreateWishlist')

	@include('wishlist.modalLogin')

	@include('wishlist.toasts')

	@if (isset($listingFilters))
		<div class="modal fade" id="searchFilters" tabindex="-1" role="dialog" aria-labelledby="searchFilters"
		     aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered position-relative justify-content-center"
			     role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="sb-title mb-0">Filters</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							@include('partials.svg-icon', ['svg_id' => 'close-icon-2', 'svg_w' => '24', 'svg_h' => '24'])
						</button>
					</div>
					<div class="modal-body datepicker-modal d-flex justify-content-center">

						@include('bookings/_filters', [ 'listingFilters' => $listingFilters ])

					</div>
					<div class="modal-footer justify-content-between">
						<button id="searchFiltersClear" type="button"
						        class="btn btn-lg btn-light rounded px-4 bk-search__clear-filters text-uppercase">@langGet('bookingProcess.searchCriteria.clear')</button>
						<button id="searchFiltersSubmit" type="button"
						        class="btn btn-lg btn-primary rounded px-4 text-uppercase d-flex align-items-center">
							<span>Show results</span>
							<div class="spinner-wrap pl-2 d-inline d-none">
								<div class="spinner-border text-primary" role="status">
									<span class="sr-only">Loading...</span>
								</div>
							</div>
						</button>
					</div>
				</div>
			</div>
		</div>
	@endif

	<script type="text/javascript">

        $(document).on("hostelz:frontUserData", function (e, data) {
            data.editURLFor = @json($editUrl);
            return data;
        })

        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (data.editURL) {
                addEditURL(data.editURL)

                $('body').on('hostelz:updateListingsSearchResultContent', function () {
                    addEditURL(data.editURL)
                });
            }
        });

        function addEditURL(editURL) {
            $('.edit-city').remove();

            if (Array.isArray(editURL)) {
                let pTag = document.createElement('p')
                pTag.classList.add('edit-city')


                editURL.forEach((item) => {
						let aTag = document.createElement('a')
						aTag.href = item.url
						aTag.classList = 'text-white d-block text-center text-decoration-underline'
						aTag.innerHTML = 'edit ' + item.target

                    	pTag.appendChild(aTag)
                    }
				)

				document.querySelector('.city-hero h1').after(pTag)
			} else {
				$('.city-hero h1').after('<a class="text-white d-block text-center text-decoration-underline edit-city" href="' + editURL + '">edit {{ $editUrl['target'] }}</a>');
			}

        }

	</script>

	<script src="{{ mix('js/citySlider.js')}}"></script>

	<script src="{{ mix('js/cityVue.js')}}"></script>

	@parent

	<script async defer
	        src="//maps.googleapis.com/maps/api/js?key={!! urlencode(config('custom.googleApiKey.clientSide')) !!}&callback=mapScriptLoaded&language={!! Languages::current()->otherCodeStandard('IANA') !!}"
	        type="text/javascript"></script>

@stop
