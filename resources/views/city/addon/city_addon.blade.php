<div id="hostelbytypes" class="hostels-by-types mb-3 border-bottom pb-3">
	<h3 class="sb-title cl-text mb-0 d-none d-lg-block">{!! langGet('city.TypeOfTravelersTitle', ['city' => $cityInfo->translation()->city]) !!}</h3>

	<h2 class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed" data-toggle="collapse" href="#hostelbytypes-content">
		{!! langGet('city.TypeOfTravelersTitle', ['city' => $cityInfo->translation()->city]) !!}
		<i class="fas fa-angle-down float-right"></i>
		<i class="fas fa-angle-up float-right"></i>
	</h2>

	<div class="mt-3 collapse d-lg-block" id="hostelbytypes-content">
		<p class="tx-small">{!! langGet('city.TypeOfTravelersText', ['city' => $cityInfo->translation()->city]) !!}</p>

		<div class="text-content">
			<ul>
				@if($topRated = $cityInfo->getTopRatedHostel( ))
					<li>{!! langGet('city.OverallBestRated', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $topRated->getURL() !!}">{{ $topRated->name }}</a></li>
				@endif

				@if($cheapest = $cityInfo->getCheapestHostel( ))
					<li>{!! langGet('city.CheapestHostelInCity', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $cheapest->getURL() !!}">{{ $cheapest->name }}</a></li>
				@endif

				@if($bestSoloTraveller = $cityInfo->getBestHostelByType('socializing'))
					<li>{!! langGet('city.BestForSoloTraveller', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $bestSoloTraveller->getURL() !!}">{{ $bestSoloTraveller->name }}</a></li>
				@endif

				@if($quietRest = $cityInfo->getBestHostelByType('quiet'))
					<li>{!! langGet('city.BestQuietRest', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $quietRest->getURL() !!}">{{ $quietRest->name }}</a></li>
				@endif

				@if($femaleSoloTraveller = $cityInfo->getBestHostelByType('female_solo_traveller'))
					<li>{!! langGet('city.BestForFemaleSoloTraveller', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $femaleSoloTraveller->getURL() !!}">{{ $femaleSoloTraveller->name }}</a></li>
				@endif

				@if($partying = $cityInfo->getBestHostelByType('partying'))
					<li>{!! langGet('city.BestPartyHostel', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $partying->getURL() !!}">{{ $partying->name }}</a></li>
				@endif

				@if($couples = $cityInfo->getBestHostelByType('couples'))
					<li>{!! langGet('city.BestCouplesHostel', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $couples->getURL() !!}">{{ $couples->name }}</a></li>
				@endif

				@if($groups = $cityInfo->getBestHostelByType('groups'))
					<li>{!! langGet('city.BestGroupsHostel', ['city' => $cityInfo->translation()->city]) !!}: <a href="{!! $groups->getURL() !!}">{{ $groups->name }}</a></li>
				@endif

				@if($seniors = $cityInfo->getBestHostelByType('seniors'))
					<li>{!! langGet('city.BestSeniors') !!}: <a href="{!! $seniors->getURL() !!}">{{ $seniors->name }}</a></li>
				@endif
			</ul>
		</div>

		<div class="row">

			@if($topRated)
				@include('partials.cards.card-1', [ 'listing' => $topRated, 'cardTagTitle' => langGet('city.BestRated')])
			@endif

			@if($cheapest)
				@include('partials.cards.card-1', [ 'listing' => $cheapest, 'cardTagTitle' => langGet('city.CheapestHostel')])
			@endif

			@if($bestSoloTraveller)
				@include('partials.cards.card-1', [ 'listing' => $bestSoloTraveller, 'cardTagTitle' => langGet('city.BestSoloTraveller')])
			@endif

			@if($quietRest)
				@include('partials.cards.card-1', [ 'listing' => $quietRest, 'cardTagTitle' => langGet('city.BestQuietRest')])
			@endif

			@if($femaleSoloTraveller)
				@include('partials.cards.card-1', [ 'listing' => $femaleSoloTraveller, 'cardTagTitle' => langGet('city.BestSoloFemaleTraveller')])
			@endif

			@if($partying)
				@include('partials.cards.card-1', [ 'listing' => $partying, 'cardTagTitle' => langGet('city.BestPartyHostelItem')])
			@endif

			@if($couples)
				@include('partials.cards.card-1', [ 'listing' => $couples, 'cardTagTitle' => langGet('city.BestCoupleHostel')])
			@endif

			@if($groups)
				@include('partials.cards.card-1', [ 'listing' => $groups, 'cardTagTitle' => langGet('city.BestGroupHostel')])
			@endif

			@if($seniors)
				@include('partials.cards.card-1', [ 'listing' => $seniors, 'cardTagTitle' => langGet('city.BestSeniors')])
			@endif

		</div>
	</div>
</div>

{{--Best for Solo-Traveller--}}
@if($bestSoloTraveller)
	<div id="besthostels" class="border-bottom pb-3" >
		<h2 class="sb-title cl-text mb-0 d-none d-lg-block">{!! langGet('city.SoloTravelersSectionTitle', ['city' => $cityInfo->translation()->city]) !!}</h2>

		<h2 class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed" data-toggle="collapse" href="#besthostels-content">
			{!! langGet('city.SoloTravelersSectionTitle', ['city' => $cityInfo->translation()->city]) !!}
			<i class="fas fa-angle-down float-right"></i>
			<i class="fas fa-angle-up float-right"></i>
		</h2>

		<div class="mt-3 collapse d-lg-block" id="besthostels-content">
			<p class="tx-small">{!! langGet('city.SoloTravelersSectionText1', ['city' => $cityInfo->translation()->city]) !!}</p>
			<p class="tx-small">{!! langGet('city.SoloTravelersSectionText2', ['city' => $cityInfo->translation()->city]) !!}</p>

			<div class="listingsList pt-3 mb-3">
				<div class="row">
					@include('partials.cards.card-1-amenities', [ 'listing' => $bestSoloTraveller, 'cardTagTitles' => ['for Solo Travellers']])

					@if($bestSoloTravellerQuite = $cityInfo->getBestHostelByTypes( 'socializing', 'quiet' ))
						@include('partials.cards.card-1-amenities', [ 'listing' => $bestSoloTravellerQuite, 'cardTagTitles' => ['for Solo Travellers', 'Quiet Rest']])
					@endif

					@if($bestSoloTravellerPartying = $cityInfo->getBestHostelByTypes( 'socializing', 'partying' ))
						@include('partials.cards.card-1-amenities', [ 'listing' => $bestSoloTravellerPartying, 'cardTagTitles' => ['for Solo Travellers', 'Party Hostel']])
					@endif
				</div>
			</div>
		</div>
	</div>
@endif