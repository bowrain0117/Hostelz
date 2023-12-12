<?php
    use Lib\Currencies;
    use App\Models\Languages;
    use App\Booking\SearchCriteria;
    use App\Services\ImportSystems\ImportSystems;
?>

@section('bookingNoSearchYet')
    <div class="bookingSearchStatusBox bookingNoSearchYet">
        <h1>@langGet('bookingProcess.ChooseYourDates1')</h1>
        <div>
            <div>
                @foreach (ImportSystems::all('onlineBooking') as $systemName => $system)
                    <div>
                        <div><img src="{!! $system->image() !!}"></div>
                    </div>
                @endforeach
            </div>
            <h3>@langGet('bookingProcess.ChooseYourDates2')</h3>
            {{-- (withInfoText ? '<div class=bookingWaitInfoTextContainer><div class=bookingWaitInfoText currentTextNum=-1></div></div>' : '')+ --}}
        </div>
    </div>
@stop

@section('bookingWait')
    <div class="bookingSearchStatusBox bookingWait">
        <h3 class="search-all-title">@langGet('bookingProcess.SearchingAll')</h3>
        <div>
            @foreach (ImportSystems::all('onlineBooking') as $systemName => $system)
                <div class="d-flex align-items-center">
                    <div>
                        <img src="{!! $system->image() !!}" alt="{{ $system->systemInfo['displayName'] }}" title="{{ $system->systemInfo['displayName'] }}">
                    </div>
                    <div class="progress-linear">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            @endforeach
            {{-- (withInfoText ? '<div class=bookingWaitInfoTextContainer><div class=bookingWaitInfoText currentTextNum=-1></div></div>' : '')+ --}}
        </div>
    </div>
@stop

@section('bookingGeneralError')
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>&nbsp; @langGet('bookingProcess.errors.misc')</div>
@endsection

{{--    We put the select currency selector in this Javascript script mostly so all those currencies don't have to get re-downloaded with every page load.  --}}
@section('selectCurrency')
    <select id="listingFilterCurrency" class="selectpicker border rounded font-weight-600 ml-lg-auto mr-lg-3">
        @foreach (Currencies::allByName() as $currencyCode => $name)
            <option title="{{ $name }}" value="{{{ $currencyCode }}}">{{{ $name }}}</option>
        @endforeach
    </select>
@endsection

<?php
$translation = [
    'save' => langGet('bookingProcess.searchCriteria.save'),
    'today' => langGet('bookingProcess.searchCriteria.today')
];
?>

@setVariableFromSection('bookingNoSearchYet')
@setVariableFromSection('bookingWait')
@setVariableFromSection('bookingGeneralError')
@setVariableFromSection('selectCurrency')

var bookingOptions = {
    defaultSearchCriteria: {!! json_encode(with(new SearchCriteria())->setToDefaults()->bookingSearchFormFields()) !!},
    searchCriteriaCookie: '{!! config('custom.bookingSearchCriteriaCookie') !!}',
    defaultStartDate: {!! SearchCriteria::DEFAULT_START_DATE_DAYS_FROM_NOW !!},
    maxNonGroupPeople: {!! SearchCriteria::MAX_NON_GROUP_PEOPLE !!},
    bookingNoSearchYet: {!! json_encode(trimLines($bookingNoSearchYet)) !!},
    bookingWait: {!! json_encode(trimLines($bookingWait)) !!},
    bookingGeneralError: {!! json_encode(trimLines($bookingGeneralError)) !!},
    localCurrencyDefaults: {!! json_encode(Currencies::$LOCAL_CURRENCY_DEFAULTS) !!},
    currencyDefaults: '{!! Currencies::defaultCurrency() !!}',
    selectCurrency: {!! json_encode(trimLines($selectCurrency), JSON_HEX_APOS) !!},
    translation: {!! json_encode($translation) !!}
};
