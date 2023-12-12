@if ($validationError)

    <br>
    <div class="alert alert-danger container"><i class="fa fa-exclamation-circle"></i>&nbsp; {{{ $validationError }}}
    </div>

@endif

@if ($rooms->isEmpty())

    @php
        $hasNoImportSystems = $rooms === null
    @endphp
    <div @class([
            'bookingSearchStatusBox' => !$hasNoImportSystems,
            'alert alert-warning text-center mb-4' => $hasNoImportSystems,
            'container'
        ])>
        <div class="text-center">
            @if ($hasNoImportSystems)
                {{-- null means the listing doesn't have any import systems with online booking --}}
                <h2 class="hero-heading h3">{{ __('global.pitySituation') }}</h2>
                <p class="text-dark">{{ __('bookingProcess.NotOfferOnlineBooking') }}</p>
            @else

                @if ($searchCriteria->roomType === 'dorm')
                    <p>
                        {{ langGet('bookingProcess.PleaseDifferentDates') }}
                        <a href="#" class="switchToPrivateSearch btn btn-primary px-2 my-2">
                            {{ langGet('bookingProcess.NoDormAvailabilityCity')}}
                        </a>
                    </p>
                @else
                    <p>
                        {{ langGet('bookingProcess.PleaseDifferentDates') }}
                        <a href="#" class="switchToDormSearch btn btn-primary px-2 my-2">
                            {{ langGet('bookingProcess.NoPrivateAvailabilityCity')}}
                        </a>
                    </p>
                @endif
            @endif
        </div>
    </div>

@else

    <div class="currencySelectorPlaceholder mb-3 mb-lg-4 container"></div>

    <div id="vue-comparison-dates">
        <comparison-dates
                :listings="{{ $listings }}"
                :rooms="{{ $rooms }}"
                :room-types="{{ $roomTypes }}"
        ></comparison-dates>
    </div>

    <script type="text/javascript">
        insertCurrencySelector('.currencySelectorPlaceholder', '{{{ $listings->first()->determineLocalCurrency() }}}', '{{{ $searchCriteria->currency }}}', bookingUpdate);
    </script>
    <script src="{{ mix('js/vue/modules/comparison-dates.js')}}"></script>

@endif