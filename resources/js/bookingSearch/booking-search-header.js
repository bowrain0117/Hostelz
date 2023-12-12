function initSearchHeader(searchCriteria, location) {
  var searchFormTop = $('#header-search-form');
  if (!searchFormTop.length) {
    return;
  }

  location = typeof location !== 'undefined' ? location : '';

  var dropdownWrap = $('.search-dropdown-wrap');
  var formInfo = $('#header-search-result__info');
  var formText = $('#header-search-result__text');
  var formWrap = $('#header-search-form__wrap');
  var overlay = $('#header-search-overlay');

  var locationField = $('#header-search-form__location input[name="location"]');

  initSearchFormTop();

  function initSearchFormTop() {
    disableCloseOnDropdownClick();

    initFormOpen();

    initFormSubmit();

    initSearchLocation(location);
    initBookingSearchDateTop(searchCriteria.startDate, searchCriteria.nights);
    initSearchRoomType();
    initSearchGuestsHeader();
    initSearchGroupType();
    initSearchGroupAgeRanges();
    initSearchRoomsHeader();
  }

  function initFormSubmit() {
    searchFormTop.find('form').submit(function (e) {
      e.preventDefault();

      if (isLocationCategoryForCity(locationField.data('category')) && locationField.data('itemId') ) {
        turnDoBookingSearchForCityTo(locationField.data('itemId'), true);
      }

      _doSearch();
      $(document).trigger('hostelz:topSearchFormSubmit');
    });

    $(document).on('hostelz:topSearchBeforeSearch', hideForm);

    $(document).on('hostelz:searchSecondDoSearch hostelz:searchMobileDoSearch hostelz:noAvailableDatesSearch hostelz:currencySearch hostelz:skipDateSearch', _doSearch);
  }

  function _doSearch() {
    doSearch(locationField);
  }

  function _doSkipSearch(locationField) {
    if (isLocationCategoryForCity(locationField.data('category')) && locationField.data('itemId') ) {
      turnDoBookingSearchForCityTo(locationField.data('itemId'), false);
    }

    $(document).trigger('hostelz:skipDateSearch');
  }

  function initSearchLocation(location) {
    var wrap = $('#header-search-form__location');
    var formTitle = wrap.find('button');
    var result = $('#header-search-result__location');
    var spinner = wrap.find('.spinner-wrap');

    var errorClass = 'is-invalid';

    var autocomlete = $('.searchLocation').devbridgeAutocomplete({
      serviceUrl: globalOptions.routes.searchAutocomplete,
      paramName: 's',
      params: {
        url: window.location.pathname,
        location: location
      },
      minChars: 0,
      groupBy: 'category',
      preserveInput: true,
      triggerSelectOnValidInput: false,
      deferRequestBy: 100,
      appendTo: $('.location-suggestion'),
      onSearchStart: function () {
        spinner.removeClass('d-none-i');
      },
      onSearchComplete: function () {
        spinner.addClass('d-none-i');

        $(this).removeClass(errorClass);
      },
      onSelect: function (suggestion) {
        userSearchHistory(suggestion);
        
        setInputData(suggestion);

        updateSearchTitle(suggestion.data.query);

        closeCurrentOptions(wrap);
        openNextOptions(wrap);
      },
      formatResult: function (suggestion, currentValue) {
        return formatSearchResult(suggestion, currentValue)
      },
    });

    if (location) {
      autocomlete.val(location);

      updateSearchTitle(location);
    }

    $(document).trigger('hostelz:searchHeaderLocationDone', locationField);

    $('.search-autocomplete-clear').click(function () {
      autocomlete.val('');
      autocomlete.devbridgeAutocomplete().clear();
      $('.autocomplete-suggestions').html('').removeClass('is-valid');
    });

    $(document).on('hostelz:searchOptionOpened', function (e, data) {
      if (data.optionField === 'location') {
        wrap.find('.searchLocation').focus();
      }
    });

    $(document).on('hostelz:searchMobileLocationChanged hostelz:searchHeroLocationChanged', function (e, suggestion) {
      setInputData(suggestion);
      updateSearchTitle(suggestion.data.query);
    });

    //  show error message
    $(document).on('hostelz:topSearchEmptyLocation', function () {
      formTitle.click();

      autocomlete.focus().addClass(errorClass);
    });

    function updateSearchTitle(value) {
      result.text( value );
      formTitle.text( value );
    }

    function setInputData(suggestion) {
      autocomlete.val(suggestion.value);
      autocomlete.data('category', suggestion.data.category);
      autocomlete.data('query', suggestion.data.query);
      autocomlete.data('itemId', suggestion.data.itemId);
      autocomlete.data('searchURL', suggestion.data.url);
    }
  }

  function openNextOptions(wrap) {
    wrap.next().find('button').click();
  }

  function closeCurrentOptions() {
    searchFormTop.find('.dropdown-menu').removeClass('show');
  }

  function initFormOpen() {
    formInfo.click(function (e) {
      showForm($(e.target));
    });

    overlay.click(function () {
      hideForm();
    });
  }

  function initSearchGuestsHeader() {
    var wrap = $('#header-search-form__guests');
    var input = $('#bookingSearchPeopleHeader');
    var result = $('#header-search-result__guests');
    var formTitle = wrap.find('button');

    input.change(function () {
      updateSearchTitle(input.val());

      $(document).trigger('hostelz:searchHeaderGuestsChanged', input.val());
    });

    $(document).on('hostelz:setFormValues', function () {
      input.val(searchCriteria.people);
      updateSearchTitle(searchCriteria.people);
    });

    $(document).on('hostelz:searchSecondGuestsChanged hostelz:searchMobileGuestsChanged hostelz:searchHeroGuestsChanged', function (e, value) {
      input.val(value).change();
    });

    function updateSearchTitle(numbers) {
      var data = numbers + ' ' + (numbers > 1 ? 'guests' : 'guest');
      result.text( data );
      formTitle.text( data );
    }
  }

  function initSearchRoomsHeader() {
    var wrap = $('#header-search-form__rooms');
    var input = $('#bookingSearchRoomsHeader');
    var result = $('#header-search-result__rooms');
    var formTitle = wrap.find('button');

    if (searchCriteria.rooms > searchCriteria.people) {
      searchCriteria.rooms = searchCriteria.people
    }

    input.change(function () {
      updateSearchTitle(input.val());

      $(document).trigger('hostelz:searchHeaderRoomsChanged', input.val());
    });

    $(document).on('hostelz:setFormValues', function () {
      input.val(searchCriteria.rooms);
      toggle(searchCriteria.roomType);
      updateSearchTitle(searchCriteria.rooms);
    });

    $(document).on('hostelz:searchSecondPrivateRoomsChanged hostelz:searchMobilePrivateRoomsChanged hostelz:searchHeroRoomsChanged', function (e, value) {
      input.val(value).change();
    });

    $(document).on('hostelz:searchRoomTypeChanged', function (e, value) {
      toggle(value);
    });

    function updateSearchTitle(numbers) {
      var data = numbers + ' ' + (numbers > 1 ? 'rooms' : 'room');
      result.text( data );
      formTitle.text( data );
    }

    function toggle(value) {
      var info = result.add(wrap);
      value === 'dorm'
        ? info.addClass('d-none-i')
        : info.removeClass('d-none-i')
    }
  }

  function initSearchRoomType() {
    var wrap = $('#header-search-form__roomType');
    var inputs = formWrap.find('[name="searchCriteria[roomType]"]');
    var result = $('#header-search-result__roomType');
    var formTitle = wrap.find('button');

    inputs.change(function () {
      var value = $(this).val();

      updateSearchTitle(value);

      $(document).trigger('hostelz:searchRoomTypeChanged', value);
    }).click(function () {
      closeCurrentOptions();
      openNextOptions(wrap);
    });

    $(document).on('hostelz:setFormValues', function () {
      inputs.filter('[value="' + searchCriteria.roomType + '"]').prop('checked', true);

      updateSearchTitle(searchCriteria.roomType);
    });

    $(document).on('hostelz:searchSecondRoomTypeChanged hostelz:searchMobileRoomTypeChanged hostelz:searchHeroRoomTypeChanged hostelz:noAvailableDatesRoomTypeChanged', function (e, value) {
      inputs.filter('[value="' + value+ '"]').prop('checked', true).change();
    });

    function updateSearchTitle(value) {
      var data = value === "dorm" ? 'Dorm Bed' : 'Private Room';
      result.text( data );
      formTitle.text( data );
    }
  }

  function initSearchGroupType() {
    var wrap = $('.bookingSearchGroupTypeHeaderWrap')
    var field = wrap.find('select');

    field.val(searchCriteria.groupType);

    field.change(function () {
      $(document).trigger('hostelz:searchHeaderGroupTypeChanged', field.val());
    });

    $(document).on('hostelz:searchSecondGroupTypeChanged hostelz:searchMobileGroupTypeChanged', function (e, value) {
      field.val(value).change();
    });
  }

  function initSearchGroupAgeRanges() {
    var wrap = $('.bookingSearchAgeRangesHeaderWrap')
    var inputs = wrap.find('input');

    checkValues(searchCriteria.groupAgeRanges);

    inputs.change(function (e) {
      // $(document).trigger('hostelz:searchHeaderGroupAgeRangesChanged', getCheckedValues());
    });

    $(document).on('hostelz:searchSecondGroupAgeRangesChanged hostelz:searchMobileGroupAgeRangesChanged', function (e, data) {
      checkValues(data.values);
    });

    function getCheckedValues() {
      return {
        values: inputs
          .filter(':checked')
          .map(function (i, e) {
            return $(e).val();
          })
          .get()
      };
    }

    function checkValues(values) {
      inputs.prop('checked', false);

      $.each(values, function (i, value) {
        inputs.filter('[value="' + value + '"]').prop('checked', true).change();
      })
    }
  }

  function disableCloseOnDropdownClick() {
    $(document).on('click', '#header-search-form .dropdown-menu', function (e) {
      e.stopPropagation();
    });
  }

  function showForm(target) {
    var lg = 992; //  window width

    if ($( document ).width() > lg) {
      formInfo.hide();
      formWrap.show(function () {
        //  open chosen option
        $('#header-search-form__' + target.data('option')).find('button').click();

        $(document).trigger('hostelz:searchOptionOpened', { optionField : target.data('option') });
      });
      formText.show();

      overlay.fadeIn( );
    } else {
      showMobileForm(target);
    }
  }

  function hideForm() {
    formInfo.show();
    formWrap.hide();
    formText.hide();

    overlay.fadeOut( );
  }

  function initBookingSearchDateTop(startDate, nights) {
    const dateRange = $('#searchDateTop');
    if (!dateRange.length) {
      return;
    }

    var wrap = $('#header-search-form__dates');
    var result = $('#header-search-result__dates');
    var formTitle = wrap.find('button');
    var dateFormat = 'YYYY-MM-DD';

    var dateRangeConfig = {
      inline:true,
      autoClose: false,
      format: dateFormat,
      separator: ' - ',
      startDate: moment().format(dateFormat),
      minDate: moment().format(dateFormat),
      maxDays: 31,
      selectForward: true,
      extraClass: 'searchDateWrap',
      container: '.datepicker-search-top',
      language: 'auto',
      showTopbar: false,
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
      },
      showShortcuts: true,
      customShortcuts:
        [
          {
            name: '<span id="header-search-date-skip" type="button" class="btn btn-primary">skip dates</span>',
            dates : function() {
              _doSkipSearch(locationField);
            }
          },
        ],
      hoveringTooltip: function(days, startTime, hoveringTime)
      {
        return getDatesTooltip(days);
      },
    };

    dateRange.dateRangePicker(dateRangeConfig);

    dateRange.data('dateRangePicker').setStart(moment(startDate).format(dateFormat));
    dateRange.data('dateRangePicker').setEnd(moment(startDate).add(nights, 'days').format(dateFormat));

    var dateField = $('#bookingSearchDate');
    var nightField = $('#bookingSearchNights');

    dateField.val(startDate);
    nightField.val(nights);

    dateRange.bind('datepicker-change', function (event, obj) {
      var value = moment(obj.date1).format('D MMM') + ' - ' + moment(obj.date2).format('D MMM');
      var startDate = moment(obj.date1).format(dateFormat);
      var nights = parseInt(moment(obj.date2).diff(moment(obj.date1), 'days'));

      if (nights === 0) {
        dateRange.data('dateRangePicker').setEnd(moment(obj.date1).add(1,'days').format(dateFormat));
        nights = 1;
      }

      updateSearchTitle(value);
      closeCurrentOptions(wrap);
      openNextOptions(wrap);

      dateField.val(startDate);
      nightField.val(nights);

      $(document).trigger('hostelz:searchHeaderDateChanged', {startDate: startDate, nights: nights, value: value});
    }).bind('datepicker-closed', function () {
      dateRange.closest('.dropdown-menu').removeClass('show');
    });

    $(document).on('hostelz:searchSecondDateChanged hostelz:searchMobileDateChanged hostelz:searchHeroDateChanged', function (e, data) {
      if (data.startDate.localeCompare(dateField.val()) !== 0 || data.nights !== parseInt(nightField.val())) {
        dateRange.data('dateRangePicker').setStart(moment(data.startDate).format(dateFormat));
        dateRange.data('dateRangePicker').setEnd(moment(data.startDate).add(data.nights, 'days').format(dateFormat));
      }
    });

    dropdownWrap
      .on('shown.bs.dropdown', function () {
        dateRange.data('dateRangePicker').open();
      });

    $(document).on('hostelz:setFormValues', function () {
      updateSearchTitle(formatTile(searchCriteria.startDate, moment(searchCriteria.startDate).add(searchCriteria.nights, 'days')));
    });

    $(document).on('hostelz:skipDateSearch hostelz:skipDateSearchMobile hostelz:skipDateCitySearchInit', function () {
      result.text( result.data('label') );
    });

    $(document).on('hostelz:topSearchFormSubmit hostelz:searchMobileDoSearch', function () {
      updateSearchTitle(formatTile(dateField.val(), moment(dateField.val()).add(nightField.val(), 'days')));
    });

    function formatTile(start, end) {
      return moment(start, "YYYY-MM-DD").format('D MMM') + ' - ' + moment(end).format('D MMM')
    }

    function updateSearchTitle(value) {
      result.text( value );
      formTitle.text( value );
    }
  }
}
