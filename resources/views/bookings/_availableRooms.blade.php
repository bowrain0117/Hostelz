@if ($validationError)

    <br>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>&nbsp; {{{ $validationError }}}</div>

@endif

@if (!$rooms)

    @php
        $hasNoImportSystems = $rooms === null
    @endphp
    <div @class([
            'bookingSearchStatusBox' => !$hasNoImportSystems,
            'alert alert-warning text-center mb-4' => $hasNoImportSystems
        ])>
        <div class="text-center">
            @if ($hasNoImportSystems) {{-- null means the listing doesn't have any import systems with online booking --}}
                <h2 class="hero-heading h3">{{ __('global.pitySituation') }}</h2>
                <p class="text-dark">{{ __('bookingProcess.NotOfferOnlineBooking') }}</p>
            @else
                
                @if ($searchCriteria->roomType === 'dorm')
                	<p>@langGet('bookingProcess.SorryNoAvailabilityDorms', ['roomtype' => $searchCriteria->roomType, 'listingname' => $listing->name])</p>
        	        <p>@langGet('bookingProcess.PleaseDifferentDates') <a href="#" class="switchToPrivateSearch btn btn-primary px-2 my-2">@langGet('bookingProcess.NoDormAvailability', ['listingname' => $listing->name])</a></p>                	
                @else
                	<p>@langGet('bookingProcess.SorryNoAvailabilityRooms', ['roomtype' => $searchCriteria->roomType, 'listingname' => $listing->name])</p>
        	        <p>@langGet('bookingProcess.PleaseDifferentDates') <a href="#" class="switchToDormSearch btn btn-primary px-2 my-2">@langGet('bookingProcess.NoPrivateAvailability', ['listingname' => $listing->name])</a></p>
                @endif
            @endif
            
            @if ($cityInfo)
        	    <p class="text-center mt-4">
                    <a
                        title="@langGet('bookingProcess.SearchAllOfCity', [ 'city' => $cityInfo->translation()->city ])"
                        class="btn btn-lg btn-outline-primary bg-primary tt-n py-2 px-sm-5 font-weight-600 rounded text-white"
                        href="{!! $cityInfo->getURL() !!}"
                        onClick="javascript:turnDoBookingSearchForCityTo({!! $cityInfo->id !!}, true);return true;"
                    >
                        {{ __('bookingProcess.ComparePrices', [ 'city' => $cityInfo->translation()->city ]) }}
                    </a>
                </p>
            @endif
        </div>
    </div>

@else

    <div class="mb-4 mb-lg-6">
        <h4 class="sb-title mb-3">@langGet($searchCriteria->roomType === 'dorm' ? 'bookingProcess.DormBedsResultsTitle' : 'bookingProcess.PrivateRoomsResultsTitle')</h4>

        <div class="currencySelectorPlaceholder mb-3 mb-lg-4"></div>

        @foreach ($rooms as $room)
            <?php
            // For convenience
            $primaryRoom = $room['primary'];
            $roomInfo = $primaryRoom->roomInfo;
            ?>

            <div class="mb-5 mb-lg-6 border-bottom pb-3">
                <div class="d-flex justify-content-between mb-3">
                    <h4 class="tx-body cl-text font-weight-600">{{{ $roomInfo->ensuite ? $roomInfo->name . ' Ensuite' : $roomInfo->name }}}</h4>

                    {{-- (for debugging) --}}
                    @if (config('custom.bookingDebugOutput'))
                        <p>{!! $primaryRoom->getDebugInfo() !!}</p>
                    @endif

                    <div class="ml-2">
                        @if ($primaryRoom->numberOfNightsWithFullAvailability() !== $searchCriteria->nights)
                            <span class="bg-accent rounded cl-text flex-center py-2 px-3">
                                <i class="cl-text fa fa-exclamation-triangle fa-fw mr-2"></i>
                                <span class="pre-title">@langGet('bookingProcess.OnlyPartialAvail') &mdash;
                                @if ($primaryRoom->maxBlocksAvailableAllNights() && $primaryRoom->maxBlocksAvailableAllNights() < $searchCriteria->numberOfBookableBlocksRequested())
                                        @langChoice($primaryRoom->roomInfo->type === 'dorm' ? 'bookingProcess.onlyBedsAvailable' : 'bookingProcess.onlyRoomsAvailable', $primaryRoom->maxBlocksAvailableAllNights(), [ 'numberAvailable' => $primaryRoom->maxBlocksAvailableAllNights() ])
                                    @elseif ($primaryRoom->maxPeopleAvailableAllNights() && $primaryRoom->maxPeopleAvailableAllNights() < $searchCriteria->people)
                                        @langChoice('bookingProcess.AvailableOnlyPeople', $primaryRoom->maxPeopleAvailableAllNights(), [ 'numPeople' => $primaryRoom->maxPeopleAvailableAllNights() ])
                                    @elseif ($primaryRoom->numberOfNightsWithFullAvailability() < $searchCriteria->nights)
                                        @langChoice('bookingProcess.AvailableOnlyNights', $primaryRoom->numberOfNightsWithFullAvailability(), [ 'numNights' => $primaryRoom->numberOfNightsWithFullAvailability() ])
                                    @else
                                        <?php logError("Not hasFullAvailability, but don't know why (this shouldn't happen)."); ?>
                                    @endif</span>
                            </span>
                        @elseif ($primaryRoom->isInfoAboutTotalAvailability)
                            @if ($primaryRoom->maxBlocksAvailableAllNights() - $searchCriteria->numberOfBookableBlocksRequested() < 5)
                                <span class="bg-accent rounded cl-text flex-center py-2 px-3">
                                    @include('partials.svg-icon', ['svg_id' => 'fire-icon', 'svg_w' => '24', 'svg_h' => '24'])
                                    <span class="pre-title">@langChoice($primaryRoom->roomInfo->type === 'dorm' ? 'bookingProcess.onlyBedsLeft' : 'bookingProcess.onlyRoomsLeft', $primaryRoom->maxBlocksAvailableAllNights(), [ 'numberAvailable' => $primaryRoom->maxBlocksAvailableAllNights() ]) &mdash; @langGet('bookingProcess.bookSoon')</span>

                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="no-last-bb">
                    <a href="{!! $primaryRoom->bookingPageLink() !!}" class="rounded-sm bg-accent p-2-1 d-flex align-items-center flex-row text-decoration-none" for="{!! $primaryRoom->imported()->getImportSystem()->shortName() !!}" target="_blank"
                       onclick="ga('send','event','single','Compare', '{{ $listing->name }}, {{$listing->city}} - {{$primaryRoom->imported()->getImportSystem()->shortName()}}')"
                       rel="nofollow">
                        @include('partials.svg-icon', ['svg_id' => strtolower($primaryRoom->imported()->getImportSystem()->systemName) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
                        <span class="ml-2 tx-small cl-subtext mr-1">from</span>
                        <span class="cl-text font-weight-600">{!! $primaryRoom->averagePricePerBlockPerNight(true) !!}</span>
                        <span class="ml-2 pre-title font-weight-bold cl-text pr-2  booking-bottom-text">
                            @if ($primaryRoom->roomInfo->type === 'dorm')
                                (@langGet('bookingProcess.PerBedNight'))
                            @else
                                (@langGet('bookingProcess.PerRoomNight'))
                            @endif
                        </span>

                        @php
                            $meetsMinimumNightsRequirement = $primaryRoom->meetsMinimumNightsRequirement()
                        @endphp

                        @if (!$meetsMinimumNightsRequirement)
                            <span class="btn-sm bg-warning-light rounded-sm ml-auto mr-2 text-xs">
                                <i class="cl-text fa fa-exclamation-triangle fa-fw mr-1"></i>
                                {{ langGet('bookingProcess.MinNights', ['numNights' => $primaryRoom->minimumNightsRequired]) }}
                            </span>
                        @endif

                        @if($meetsMinimumNightsRequirement && isset($minimumNights[$primaryRoom->imported()->getImportSystem()->systemName]))
                            <span class="btn-sm bg-warning-light rounded-sm ml-auto mr-2 text-xs">
                                <i class="cl-text fa fa-exclamation-triangle fa-fw mr-1"></i>
                                {{ langGet('bookingProcess.MinNights', ['numNights' => $minimumNights[$primaryRoom->imported()->getImportSystem()->systemName]]) }}
                            </span>
                        @endif

                        @if (isset($room['savingsPercent']) && $room['savingsPercent'] > 2)
                            <span class="btn btn-danger rounded-sm ml-auto"><span class="tx-small text-lowercase">@langGet('bookingProcess.YouSave')</span> <span class="font-weight-600">{!! $room['savingsPercent'] !!}%</span></span>
                        @endif
                    </a>

                    @foreach ($room['otherPrices'] as $otherPrice)
                        <a href="{!! $otherPrice->bookingPageLink() !!}" class="rounded-sm border-bottom bg-white p-2-1 d-flex align-items-center flex-row" for="{!! $otherPrice->imported()->getImportSystem()->shortName() !!}" target="_blank"
                           onclick="ga('send','event','single','Compare', '{{ $listing->name }}, {{$listing->city}} - {{$otherPrice->imported()->getImportSystem()->shortName()}}')"
                           rel="nofollow">
                            @include('partials.svg-icon', ['svg_id' => strtolower($otherPrice->imported()->getImportSystem()->systemName) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
                            <span class="ml-2 tx-small cl-subtext mr-1">from</span>
                            <span class="cl-text font-weight-600">{!! $otherPrice->averagePricePerBlockPerNight(true) !!}</span>
                            <span class="ml-2 pre-title font-weight-bold cl-text pr-2  booking-bottom-text">
                                @if ($primaryRoom->roomInfo->type === 'dorm')
                                    (@langGet('bookingProcess.PerBedNight'))
                                @else
                                    (@langGet('bookingProcess.PerRoomNight'))
                                @endif
                            </span>

                            @php
                                $meetsMinimumNightsRequirement = $otherPrice->meetsMinimumNightsRequirement();
                            @endphp

                            @if (!$meetsMinimumNightsRequirement)
                                <span class="btn-sm bg-warning-light rounded-sm ml-auto mr-2 text-xs">
                                    <i class="cl-text fa fa-exclamation-triangle fa-fw mr-1"></i>
                                    {{ langGet('bookingProcess.MinNights', ['numNights' => $otherPrice->minimumNightsRequired]) }}
                                </span>
                            @endif

                            @if($meetsMinimumNightsRequirement && isset($minimumNights[$otherPrice->imported()->getImportSystem()->systemName]))
                                <span class="btn-sm bg-warning-light rounded-sm ml-auto mr-2 text-xs">
                                    <i class="cl-text fa fa-exclamation-triangle fa-fw mr-1"></i>
                                    {{ langGet('bookingProcess.MinNights', ['numNights' => $minimumNights[$otherPrice->imported()->getImportSystem()->systemName]]) }}
                                </span>
                            @endif
                        </a>
                    @endforeach

                    @foreach ($room['systemsNotUsed'] as $systemName => $system)
                        <label class="disabled rounded-sm border-bottom bg-white p-2-1 d-flex align-items-center flex-row mb-0 d-block" for="{!! $system->shortName() !!}">
                            @include('partials.svg-icon', ['svg_id' => strtolower($systemName) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
                            <span class="ml-2 tx-small cl-subtext">@langGet('bookingProcess.noSystemAvailability')</span>
                        </label>
                    @endforeach

                </div>

                <div class="d-flex flex-row justify-content-center justify-content-lg-end align-items-center booking-bottom">
                    <span class="sb-title cl-text mb-0 pr-2 booking-bottom-text">
                        @langGet('bookingProcess.TotalBestPrice'):
                    </span>
                    <span class="sb-title cl-text mb-0 pr-3 booking-bottom-price">
                        {!! $primaryRoom->totalPrice(true) !!}
                    </span>
                    <a target="_blank" href="{!! $primaryRoom->bookingPageLink() !!}" class="btn btn-lg btn-primary rounded px-4 order-lg-1  booking-bottom-button">
                        @langGet('bookingProcess.BookNow')
                        @if (isset($room['savingsPercent']) && $room['savingsPercent'] > 2)
                            <span>(@langGet('bookingProcess.YouSave') {!! $room['savingsPercent'] !!}%)</span>
                        @endif
                    </a>
                </div>
            </div>
        @endforeach

    </div>
    
    <script type="text/javascript">
        insertCurrencySelector('.currencySelectorPlaceholder', '{{{ $listing->determineLocalCurrency() }}}', '{{{ $searchCriteria->currency }}}', bookingUpdate);
        insertCurrencySelector('.currencySelectorMenuPlaceholder', '{{{ $listing->determineLocalCurrency() }}}', '{{{ $searchCriteria->currency }}}')
    </script>

@endif
