<?php
    use Lib\Currencies;
    use App\Models\Languages;
    use App\Booking\SearchCriteria;
?>

@section('bookingNoSearchYet')
    <div class="bookingSearchStatusBox bookingNoSearchYet">
        <h1>@langGet('bookingProcess.ChooseYourDates1')</h1>
        <div>
            <div>
                @foreach (App\Services\ImportSystems\ImportSystems::all('onlineBooking') as $systemName => $system)
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
{{--            @foreach (ImportSystems::all('onlineBooking') as $systemName => $system)--}}
{{--                <div>--}}
{{--                    <div><img src="{!! $system->image() !!}"></div>--}}
{{--                    @langGet('bookingProcess.Checking') <i class="fa fa-spinner fa-spin"></i>--}}
{{--                </div>--}}
{{--            @endforeach--}}
            {{-- (withInfoText ? '<div class=bookingWaitInfoTextContainer><div class=bookingWaitInfoText currentTextNum=-1></div></div>' : '')+ --}}
        </div>
    </div>
@stop

@section('bookingGeneralError')
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>&nbsp; @langGet('bookingProcess.errors.misc')</div>
@endsection

function initializeBookingSearchForm()
{
    var defaultSearchCriteria = {!! json_encode(with(new SearchCriteria())->setToDefaults()->bookingSearchFormFields()) !!};
	var searchCriteria = getMultiCookie('{!! config('custom.bookingSearchCriteriaCookie') !!}', false);
	var cookieWasAlreadySet = true;

	if (!searchCriteria || !searchCriteria.startDate || !$.isArray(searchCriteria.groupAgeRanges)) {
		cookieWasAlreadySet = false;
		searchCriteria = defaultSearchCriteria;
		/* Set the current default startDate (because this JS file is cached and may be quite old) */
		var startDate = new Date();
		startDate.setDate(startDate.getDate() + {!! SearchCriteria::DEFAULT_START_DATE_DAYS_FROM_NOW !!});
		searchCriteria.startDate = startDate.toISOString().split('T')[0]; /* YYYY-MM-DD format */
	} else {
	    /* merge searchCriteria with defaultSearchCriteria just to add in any missing fields that may have been added */
    	searchCriteria = $.extend({ }, defaultSearchCriteria, searchCriteria);
	}

	setFormValues($('.bookingSearchForm'), 'searchCriteria', searchCriteria);
	bookingSearchUpdateFieldVisibility();

    $('.bookingSearchForm input, .bookingSearchForm select').change(function (event) {
        bookingSearchUpdateFieldVisibility();
    });


	// Set the initial value
    initBookingSearchDate(searchCriteria.startDate, searchCriteria.nights);

	return { 'cookieWasAlreadySet': cookieWasAlreadySet, 'searchCriteria': searchCriteria };
}

function initBookingSearchDate(startDate, nights) {
    var dateRange = $('#searchDate');

    var dateRangeConfig = {
        autoClose: true,
        format: 'DD MMM. YYYY',
        separator: ' - ',
        startDate: new Date(),
        endDate: false,
        maxDays: 31,
        selectForward: true,
        applyBtnClass: 'btn btn-primary',
        container: '.datepicker-container',
        inline: true,
        language: 'auto',
        showShortcuts: true,
        customShortcuts:
        [
            {
                name: 'Cleare',
                dates: function (a, b) {
                    dateRange.data('dateRangePicker').clear();
                }
            },
            {
                name: 'Close',
                dates: function () {
                    dateRange.data('dateRangePicker').close();
                }
            }
        ],
        showDateFilter: function (time, date) {
            return '<div style="padding:5px;">\
                <span style="">' + date + '</span>\
            </div>';
        },
        customOpenAnimation: function (cb) {
            $(this).fadeIn(300, cb);
        },
        customCloseAnimation: function (cb) {
            $(this).fadeOut(300, cb);
        }
    };

    dateRange.dateRangePicker(dateRangeConfig);

    dateRange.data('dateRangePicker').setStart(moment(startDate).format('DD MMM. YYYY'))
    dateRange.data('dateRangePicker').setEnd(moment(startDate).add(nights, 'days').format('DD MMM. YYYY'))

    dateRange.bind('datepicker-change',function(event,obj) {
{{--    obj will be something like this:
            {
                date1: (Date object of the earlier date),
                date2: (Date object of the later date),
                value: "2013-06-05 to 2013-06-07"
            }
--}}

        $('#bookingSearchDate').val(moment(obj.date1).format('YYYY-MM-DD'));
        $('#bookingSearchNights').val(moment(obj.date2).diff(moment(obj.date1), 'days'));
    })

    dateRange.bind('datepicker-closed',function(){
        doAvailabilitySearch(true)
    });
}

function bookingSearchSetCookie(searchCriteria)
{
    setMultiCookie('{!! config('custom.bookingSearchCriteriaCookie') !!}', searchCriteria);
}

/* This needs searchCriteria to already be initialized with values so it knows what fields to fetch from the form. */

function bookingSearchGetFormValues(searchCriteria)
{
	getFormValues($('.bookingSearchForm'), 'searchCriteria', searchCriteria);
}

function bookingSearchUpdateFieldVisibility()
{
    if ($('.bookingSearchRoomType input:checked').val() == 'private') {
        $('.bookingSearchRooms').show(); // show "Rooms"
    } else {
        $('.bookingSearchRooms').hide(); // hide "Rooms"
    }

    if ($('#bookingSearchPeople').val() > {!! SearchCriteria::MAX_NON_GROUP_PEOPLE !!})
        $('.bookingSearchGroup').removeClass('d-none'); // show group fields
    else
        $('.bookingSearchGroup').addClass('d-none'); // hide group fields
}

function bookingShowNoSearchYet(selector)
{
    @setVariableFromSection('bookingNoSearchYet')
    $(selector).html({!! json_encode(trimLines($bookingNoSearchYet)) !!});
}

function bookingShowWait(selector, withInfoText)
{
    @setVariableFromSection('bookingWait')
    $(selector).html({!! json_encode(trimLines($bookingWait)) !!});

    // (todo) if (withInfoText) updateBookingWaitInfoText();
}

function bookingGeneralErrorMessage(selector)
{
    @setVariableFromSection('bookingGeneralError')
    $(selector).html({!! json_encode(trimLines($bookingGeneralError)) !!});
}


/** Currency Selector **/

// We put the select currency selector in this Javascript script mostly so all those currencies don't have to get re-downloaded with every page load.

@section('selectCurrency')
    <select class="form-control input-sm">
        @foreach (Currencies::allByName() as $currencyCode => $name)
            <option value="{{{ $currencyCode }}}">{{{ $name }}}</option>
        @endforeach
    </select>
@endsection

function insertCurrencySelector(jquerySelector, localCurrencyCode, selectedCurrency, selectEventHandler /* optional */)
{
    @setVariableFromSection('selectCurrency')
    $(jquerySelector).html({!! json_encode(trimLines($selectCurrency)) !!});

    // These currencies are the only ones that will be selected by default when they are the local currency
    if ({!! json_encode(Currencies::$LOCAL_CURRENCY_DEFAULTS) !!}.indexOf(localCurrencyCode) > -1) {
        // If there isn't a selected current, make the local currency the '' one so that will be the selected default
        // (because selectedCurrency will have the value of '' by default)
        if (selectedCurrency == '') {
            $(jquerySelector + ' select option[value="'+localCurrencyCode+'"]').val('');
        }
    } else {
        // Move the local currency option to the top of the list
        var $option = $(jquerySelector + ' select option[value="'+localCurrencyCode+'"]').clone();
        var label = $option.text();
        $option.text(label + ' (local currency)');
        $(jquerySelector + ' select').prepend($option);

        // If there isn't a selected currency, make the default currency the one '' so that will be the selected default
        // (because selectedCurrency will have the value of '' by default)
        if (selectedCurrency == '') {
            $(jquerySelector + ' select option[value="{!! Currencies::defaultCurrency() !!}"]').val('');
        }
    }

    // Select the selected currency
    $(jquerySelector + ' select').val(selectedCurrency);

    // Handle select option events
    $(jquerySelector + ' select').change(function (e) {
        // set the hidden input on the bookingSearchForm
        $('.bookingSearchForm input[name="searchCriteria\\[currency\\]"]').val($(this).val());
        if (typeof selectEventHandler != 'undefined') selectEventHandler($(this));
    });
}
