{{--SHORTCUT/CHEATSHEET--}}
@php use App\Enums\CategorySlp;use App\Models\SpecialLandingPage; @endphp
@if($cityInfo->hostelCount > 0)
	<div class="mb-3 mb-lg-5 mt-lg-5 border-bottom pb-3">
		<h2 class="sb-title cl-text pt-lg-3 d-none d-lg-block"
			id="statistics">{!! langGet('city.StatsTitle', [ 'city' => $cityInfo->translation()->city]) !!}</h2>

		<p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed"
		   data-toggle="collapse" href="#statistics-content">
			{!! langGet('city.StatsTitle', [ 'city' => $cityInfo->translation()->city]) !!}
			<i class="fas fa-angle-down float-right"></i>
			<i class="fas fa-angle-up float-right"></i>
		</p>

		<div class="mt-3 collapse d-lg-block" id="statistics-content">
			<div class="tx-small mb-3">{!! langGet('city.StatsText', [ 'city' => $cityInfo->translation()->city]) !!}</div>

			<div class="">
				{{-- Total number of Hostels	--}}
				@if($cityInfo->hostelCount)
					<div class="mb-3 mb-lg-5">
						<div class="card border-0 shadow rounded-lg bg-light">
							<div class="card-body p-3 p-lg-5">
								<div class="d-flex align-items-center justify-content-start">
									<span class="title-2 cl-text font-weight-bold mr-3">{{ $cityInfo->hostelCount }}</span>
									<span class="tx-small cl-subtext">{!! langGet('city.TotalNumber') !!}</span>
								</div>
							</div>
						</div>
					</div>
				@endif

				{{-- Average hostel price --}}
				@if($priceAVG && ($priceAVG['dorm'] > 0 || $priceAVG['private'] > 0))
					<div class="mb-3 mb-lg-5">
						<div class="card border-0 shadow rounded-lg bg-light">
							<div class="card-body p-3 p-lg-5">
								<div class="d-flex align-items-center justify-content-start flex-column">
									@if ($priceAVG['dorm'] > 0)
										<div class="w-100 mb-3 d-flex align-items-center">
											<span class="title-2 cl-text font-weight-bold mr-3">${{ $priceAVG['dorm'] }}</span>
											<span class="tx-small cl-subtext">{!! langGet('city.AverageDormPrice') !!}</span>
										</div>
									@endif

									@if ($priceAVG['private'] > 0)
										<div class="w-100 d-flex align-items-center">
											<span class="title-2 cl-text font-weight-bold mr-3">${{ $priceAVG['private'] }}</span>
											<span class="tx-small cl-subtext">{!! langGet('city.AveragePrivatePrice') !!}</span>
										</div>
									@endif
								</div>
							</div>
						</div>
					</div>
				@endif

				{{-- Cheapest hostel --}}
				@if($cheapest = $cityInfo->getCheapestHostel( ))
					<div class="mb-3 mb-lg-5">
						<div class="card border-0 shadow rounded-lg bg-light">
							<div class="card-body p-3 p-lg-5">
								<div class="d-flex align-items-center justify-content-start flex-column">
									<div class="w-100 mb-3 d-flex align-items-center">
										<span class="title-2 cl-text font-weight-bold mr-3">
											@if (($averageDormPrice = $cheapest->getDormAveragePrice()) > 0)
												${{ $averageDormPrice }}
											@else
												<i class="fas fa-search-dollar"></i>
											@endif
										</span>
										<?php
										$slp = SpecialLandingPage::forCity($cityInfo->city)->where(
												'category',
												CategorySlp::Cheap
											)->first();
										?>
										@if($slp)
											<a class="tx-small text-primary"
											   href="{{ $slp->path }}"
											   target="_blank"
											   title="Cheapest hostel in {{ $cityInfo->translation()->city }}"
											>
												{!! langGet('city.CheapestHostelInCity', ['city' => $cityInfo->translation()->city]) !!}
											</a>
										@else
											<span class="tx-small cl-subtext">{!! langGet('city.CheapestHostelInCity', ['city' => $cityInfo->translation()->city]) !!}</span>
										@endif

									</div>

									<div class="w-100 tx-small">
										<a class="cl-text"
										   href="{!! $cheapest->getURL() !!}">{{{ $cheapest->name }}}</a> {!! langGet('city.isCheapest') !!}
									</div>
								</div>
							</div>
						</div>
					</div>
				@endif

				{{-- Party Hostels --}}
				@if(($partyHostels = $cityInfo->getPartyHostels( )) && $partyHostels['count'] > 0)
					<div class="mb-3 mb-lg-5">
						<div class="card border-0 shadow rounded-lg bg-light">
							<div class="card-body p-3 p-lg-5">
								<div class="d-flex align-items-center justify-content-start flex-column">
									<div class="w-100 mb-3 d-flex align-items-center">
										<span class="title-2 cl-text font-weight-bold mr-3">{{ $partyHostels['count'] }}</span>
										<span class="tx-small cl-subtext">{!! langGet('city.PartyHostelsInCity', ['city' => $cityInfo->translation()->city]) !!}</span>
									</div>

									<div class="w-100 tx-small">
										<a class="cl-text"
										   href="{{ $partyHostels['best']->getURL() }}">{{ $partyHostels['best']->name }}</a> {!! langGet('city.isBestRatedParty') !!}
									</div>
								</div>
							</div>
						</div>
					</div>
				@endif

				{{-- Most located in --}}
				@if($Neighborhood = $cityInfo->getMostRatingNeighborhood())
					<div class="mb-3 mb-lg-5">
						<div class="card border-0 shadow rounded-lg bg-light">
							<div class="card-body p-3 p-lg-5">
								<div class="d-flex align-items-center justify-content-start flex-column">
									<div class="w-100 mb-3 d-flex align-items-center">
										<span class="title-2 cl-text font-weight-bold mr-3"><i
													class="fas fa-map-marker-alt"></i></span>
										<span class="tx-small cl-subtext">{!! langGet('city.MostNeighborhoods', ['city' => $cityInfo->translation()->city]) !!}</span>
									</div>

									<div class="w-100 cl-text font-weight-600">
										{{ implode(', ', $Neighborhood) }}
									</div>
								</div>
							</div>
						</div>
					</div>
				@endif

				{{-- Average Rating of all Hostels --}}
				@if($cityAVGHostelsRating = $cityInfo->getAVGHostelsRating())
					<div class="mb-3 mb-lg-5">
						<div class="card border-0 shadow rounded-lg bg-light">
							<div class="card-body p-3 p-lg-5">
								<div class="d-flex align-items-center justify-content-start">
									<span class="title-2 cl-text font-weight-bold mr-3">{{ $cityAVGHostelsRating / 10 }}</span>
									<span class="tx-small cl-subtext">{!! langGet('city.AverageRating') !!}</span>
								</div>
							</div>
						</div>
					</div>
				@endif

			</div>
		</div>
	</div>
@endif