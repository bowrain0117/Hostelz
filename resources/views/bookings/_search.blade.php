<section class="container mb-5 mb-lg-6">
	<div id="listingsSearchResult">
		<div class="search-result-spinner-toggle">
			<h3 class="search-all-title">@langGet('bookingProcess.SearchingAll')</h3>
			<div>
				@foreach (App\Services\ImportSystems\ImportSystems::all('onlineBooking') as $systemName => $system)
					<div class="d-flex align-items-center mt-3 justify-content-around">
						<div>
							<img src="{!! $system->image() !!}" alt="{{ $system->systemInfo['displayName'] }}"
							     title="{{ $system->systemInfo['displayName'] }}">
						</div>
						<div class="progress-linear w-75 ml-3">
							<div class="progress-bar"></div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
	</div>
</section>
