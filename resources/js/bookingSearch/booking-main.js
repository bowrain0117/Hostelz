function initializeBookingSearchForm( location ) {
  var defaultSearchCriteria = bookingOptions.defaultSearchCriteria;
  var searchCriteria = getMultiCookie(bookingOptions.searchCriteriaCookie, false);

  var cookieWasAlreadySet = true;

  if (!searchCriteria || !searchCriteria.startDate || !$.isArray(searchCriteria.groupAgeRanges)) {
    cookieWasAlreadySet = false;
    searchCriteria = defaultSearchCriteria;
    /* Set the current default startDate (because this JS file is cached and may be quite old) */
    var startDate = new Date();
    startDate.setDate(startDate.getDate());
    searchCriteria.startDate = startDate.toISOString().split('T')[0]; /* YYYY-MM-DD format */
  } else {
    /* merge searchCriteria with defaultSearchCriteria just to add in any missing fields that may have been added */
    searchCriteria = $.extend({}, defaultSearchCriteria, searchCriteria);
  }

  if (moment(searchCriteria.startDate).isBefore(moment())) {
    searchCriteria.startDate = moment().format('YYYY-MM-DD');
    //  update cookies
    bookingSearchSetCookie(searchCriteria);
  }

  // Set the initial value
  initSearchHeader(searchCriteria, location);
  initMobileSearch(searchCriteria, location);
  initSearchListing(searchCriteria);

  $(document).trigger('hostelz:searchInit', searchCriteria);

  setFormValues($('.bookingSearchForm'), 'searchCriteria', searchCriteria);
  bookingSearchUpdateFieldVisibility();

  $('.bookingSearchFormSecond, .bookingSearchForm, #bookingSearchFormMobile').find('input, select').change(function (event) {
    bookingSearchUpdateFieldVisibility();
  });

  return {'cookieWasAlreadySet': cookieWasAlreadySet, 'searchCriteria': searchCriteria};
}

function doSearch(locationField) {
  $(document).trigger( 'hostelz:doSearch', {
    locationField: typeof locationField !== 'undefined' && typeof locationField.data('itemId') !== 'undefined'
      ? {
        itemId: locationField.data('itemId'),
        query: locationField.data('query'),
        searchURL: locationField.data('searchURL'),
        category: locationField.data('category'),
      }
      : null
  } );
}

function bookingSearchSetCookie(searchCriteria) {
  setMultiCookie(bookingOptions.searchCriteriaCookie, searchCriteria);
}

/* This needs searchCriteria to already be initialized with values so it knows what fields to fetch from the form. */

function bookingSearchGetFormValues(searchCriteria) {
  getFormValues($('.bookingSearchForm'), 'searchCriteria', searchCriteria);
}

function bookingSearchUpdateFieldVisibility() {
  if ($('.bookingSearchRoomType input:checked').val() === 'private') {
    $('.bookingSearchRooms').removeClass('d-none'); // show "Rooms"
  } else {
    $('.bookingSearchRooms').addClass('d-none');// hide "Rooms"
  }

  if ($('#bookingSearchPeopleHeader').val() > bookingOptions.maxNonGroupPeople)
    $('.bookingSearchGroup').removeClass('d-none'); // show group fields
  else
    $('.bookingSearchGroup').addClass('d-none'); // hide group fields
}

function bookingShowNoSearchYet(selector) {
  $(selector).html(bookingOptions.bookingNoSearchYet);
}

function bookingShowWait(selector, withInfoText) {
  $(selector).html(bookingOptions.bookingWait);

  // (todo) if (withInfoText) updateBookingWaitInfoText();
}

function bookingGeneralErrorMessage(selector) {
  $(selector).html(bookingOptions.bookingGeneralError);
}

/** Currency Selector **/

function insertCurrencySelector(jquerySelector, localCurrencyCode, selectedCurrency, selectEventHandler /* optional */) {
  if (! $(jquerySelector).length) return;

  $(jquerySelector).html(bookingOptions.selectCurrency);

  // These currencies are the only ones that will be selected by default when they are the local currency
  if (bookingOptions.localCurrencyDefaults.indexOf(localCurrencyCode) > -1) {
    // If there isn't a selected current, make the local currency the '' one so that will be the selected default
    // (because selectedCurrency will have the value of '' by default)
    if (selectedCurrency == '') {
      $(jquerySelector + ' select option[value="' + localCurrencyCode + '"]').val('');
    }
  } else {
    // Move the local currency option to the top of the list
    var $option = $(jquerySelector + ' select option[value="' + localCurrencyCode + '"]').clone();
    var label = $option.text();
    $option.text(label + ' (local currency)');
    $(jquerySelector + ' select').prepend($option);

    // If there isn't a selected currency, make the default currency the one '' so that will be the selected default
    // (because selectedCurrency will have the value of '' by default)
    if (selectedCurrency == '') {
      $(jquerySelector + ' select option[value="' + bookingOptions.currencyDefaults + '"]').val('');
    }
  }

  // Select the selected currency
  $(jquerySelector + ' select').val(selectedCurrency);

  // Handle select option events
  $(jquerySelector + ' select').change(function (e) {
    // set the hidden input on the bookingSearchForm
    $('.bookingSearchForm input[name="searchCriteria\\[currency\\]"]').val($(this).val());

    $(document).trigger('hostelz:currencySearch');

    // if (typeof selectEventHandler != 'undefined') selectEventHandler($(this));
  });
}

function initializeTopHeaderSearch()
{
  var result = initializeBookingSearchForm();
  var searchCriteria = result.searchCriteria;

  $('.bookingSearchForm .bookingSubmitButton').removeClass('disabled'); /* remove the 'disabled' class now that the form is ready to be submitted */

  $(document).on('hostelz:doSearch', function (e, data) {
    bookingSearchGetFormValues(searchCriteria);
    bookingSearchSetCookie(searchCriteria);

    if (data.locationField === null) {
      $(document).trigger('hostelz:topSearchEmptyLocation');

      return true;
    }

    $(document).trigger('hostelz:topSearchBeforeSearch');

    window.location.href = data.locationField.searchURL !== ''
      ? data.locationField.searchURL
      : '/search?' + $.param( { search: data.locationField.query } );

  });
}

function formatSearchResult(suggestion, currentValue)
{
  if (!currentValue) {
    suggestion.data.category = 'nearby destinations'
  }

  var pattern = '(' + currentValue.trim().replace(/[|\\{}()[\]^$+*?.]/g, "\\$&") + ')';
  var value = suggestion.value;

  if (suggestion.data.highlighted) {
    value = value.replace(suggestion.data.query, suggestion.data.highlighted)
        .replaceAll('<em>', '<strong>')
        .replaceAll('</em>', '</strong>');
  } else {
    value = value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
  }

  // clear html tags
  value = value.replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/&lt;(\/?strong)&gt;/g, '<$1>');

  if (suggestion.data.category === 'city' || suggestion.data.category === 'nearby destinations') {
    value = '<svg width="24" height="25"><use xlink:href="#map-place"></use></svg>' + value;
  } else if (suggestion.data.img !== '') {
    value = '<img class="autocomplete-img" src="' + suggestion.data.img + '">' + value;
  }

  return value;
}

function isLocationCategoryForCity(category) {
  var citiesCategories = ['city', 'nearby destinations'];
  return citiesCategories.includes(category);
}

function getDatesTooltip(days) {
  if (days <= 1) {
    return '';
  }

  var nights = days - 1;
  return days + ' ' + 'Days / ' + nights + ' ' + (nights > 1 ? 'Nights' : 'Night');
}
