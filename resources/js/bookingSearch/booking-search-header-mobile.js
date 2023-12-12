function showMobileForm( target ) {
  switch (target.data('option')) {
    case 'location':
      $(document).trigger('hostelz:searchMobileLocationShow');
      $('#modalMobileSearchLocation')
        .modal('show')
        .on('shown.bs.modal', function (e) {
          $(this).find('.searchLocationMobile').focus();
        });
      break;
    case 'dates':
      $(document).trigger('hostelz:searchMobileDatesShow');
      break;
    case 'rooms':
    case 'roomType':
      $(document).trigger('hostelz:searchMobileRoomTypeShow');
      break;
    case 'guests':
      $(document).trigger('hostelz:searchMobileGuestsShow');
      break;
    default:
      console.log('unknown action');
  }
}

function initMobileSearch(searchCriteria, location) {
  var bookingSearchFormMobile = $('#bookingSearchFormMobile');
  var locationFieldMobile = bookingSearchFormMobile.find('.searchLocationMobile');

  initLocationMobileSearch(location);
  initDatesMobileSearch(searchCriteria.startDate, searchCriteria.nights);
  initRoomTypeMobileSearch(searchCriteria);
  initPrivateRoomsMobileSearch(searchCriteria);
  initGuestsMobileSearch(searchCriteria);
  initSearchGroupType(searchCriteria);
  initSearchGroupAgeRanges(searchCriteria);

  initButtons();

  function initButtons() {
    $('.mobile-search-prev').click(function () {
      var btn = $(this);
      var target = btn.data('target');

      if (target) {
        $(document).trigger('hostelz:' + target);
      }
    });

    $('.searchMobileButton').click(function () {
      if (isLocationCategoryForCity(locationFieldMobile.data('category')) && locationFieldMobile.data('itemId') ) {
        turnDoBookingSearchForCityTo(locationFieldMobile.data('itemId'), true);
      }

      $('.modal').modal('hide');
      $(document).trigger('hostelz:searchMobileDoSearch');
    });

    bookingSearchFormMobile.find('#header-search-date-skip').click(function () {
      if (isLocationCategoryForCity(locationFieldMobile.data('category')) && locationFieldMobile.data('itemId') ) {
        turnDoBookingSearchForCityTo(locationFieldMobile.data('itemId'), false);
      }

      $('.modal').modal('hide');

      $(document).trigger('hostelz:searchMobileDoSearch');

      $(document).trigger('hostelz:skipDateSearchMobile');
    });
  }

  function initGuestsMobileSearch(searchCriteria) {
    var wrap = $('#modalMobileSearchGuests');
    var input = $('#bookingSearchPeopleMobile');

    input.change(function () {
      $(document).trigger('hostelz:searchMobileGuestsChanged', input.val());
    });

    $(document).on('hostelz:setFormValues', function () {
      input.val(searchCriteria.people);
    });

    $(document).on('hostelz:searchMobileDateChanged hostelz:searchMobileGuestsShow', function () {
      wrap.modal('show');
    });

    $(document).on('hostelz:searchHeaderGuestsChanged', function (e, value) {
      if (input.val() !== value) {
        input.val(value);
      }
    });
  }

  function initSearchGroupType(searchCriteria) {
    var wrap = $('.bookingSearchGroupTypeMobileWrap')
    var field = wrap.find('select');

    field.val(searchCriteria.groupType);

    field.change(function () {
      $(document).trigger('hostelz:searchMobileGroupTypeChanged', field.val());
    });

    $(document).on('hostelz:searchHeaderGroupTypeChanged', function (e, value) {
      field.val(value);
    });
  }

  function initSearchGroupAgeRanges(searchCriteria) {
    var wrap = $('.bookingSearchAgeRangesMobileWrap')
    var inputs = wrap.find('input');

    checkValues(searchCriteria.groupAgeRanges);

    inputs.change(function () {
      $(document).trigger('hostelz:searchMobileGroupAgeRangesChanged', getCheckedValues());
    });

    $(document).on('hostelz:searchHeaderGroupAgeRangesChanged', function (e, data) {
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
        inputs.filter('[value="' + value + '"]').prop('checked', true);
      })
    }
  }

  function initPrivateRoomsMobileSearch(searchCriteria) {
    var wrap = $('.bookingSearchRoomsMobileWrap');
    var input = $('#bookingSearchRoomsMobile');

    input.change(function () {
      $(document).trigger('hostelz:searchMobilePrivateRoomsChanged', input.val());
    });

    $(document).on('hostelz:setFormValues', function () {
      input.val(searchCriteria.rooms);
      toggle(searchCriteria.roomType);
    });

    $(document).on('hostelz:searchRoomTypeChanged', function (e, value) {
      toggle(value);
    });

    $(document).on('hostelz:searchHeaderRoomsChanged', function (e, value) {
      if (input.val() !== value) {
        input.val(value);
      }
    });

    function toggle(value) {
      value === 'dorm'
        ? wrap.hide()
        : wrap.show()
    }
  }

  function initRoomTypeMobileSearch() {
    var wrap = $('#modalMobileSearchRoomType');
    var inputs = wrap.find('[name="searchCriteriaMobile[roomType]"]');

    inputs.change(function () {
      var value = $(this).val();
      $(document).trigger('hostelz:searchMobileRoomTypeChanged', value);
    });

    $(document).on('hostelz:setFormValues', function () {
      inputs.filter('[value="' + searchCriteria.roomType + '"]').prop('checked', true);
    });

    $(document).on('hostelz:searchRoomTypeChanged', function (e, value) {
      inputs.filter('[value="' + value + '"]').prop('checked', true);
    });

    // $(document).on('hostelz:searchMobileDateChanged hostelz:searchMobileRoomTypeShow', function () {
    //   wrap.modal('show');
    // });
  }

  function initDatesMobileSearch(startDate, nights) {
    var dateRange = $('#searchDateMobile');
    if (!dateRange.length) {
      return;
    }

    var showNextOption = true;

    var wrap = $('#modalMobileSearchDates');
    var formTitle = wrap.find('.form-title');
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
      extraClass: '',
      container: '.modalMobileSearchDatesContainer',
      language: 'auto',
      showShortcuts: false,
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
      hoveringTooltip: function(days, startTime, hoveringTime)
      {
        return getDatesTooltip(days);
      },
    };

    dateRange.dateRangePicker(dateRangeConfig);

    dateRange.data('dateRangePicker').setStart(moment(startDate).format(dateFormat));
    dateRange.data('dateRangePicker').setEnd(moment(startDate).add(nights, 'days').format(dateFormat));

    dateRange.bind('datepicker-change', function (e, obj) {
      var value = moment(obj.date1).format('D MMM') + ' - ' + moment(obj.date2).format('D MMM');
      var startDate = moment(obj.date1).format(dateFormat);
      var nights = parseInt(moment(obj.date2).diff(moment(obj.date1), 'days'));

      if (nights === 0) {
        dateRange.data('dateRangePicker').setEnd(moment(obj.date1).add(1,'days').format(dateFormat));
        nights = 1;
      }

      updateSearchTitle(value);

      wrap.modal('hide');
      dateRange.data('dateRangePicker').close();

      if (showNextOption) {
        $(document).trigger('hostelz:searchMobileDateChanged', {startDate: startDate, nights: nights});
      } else {
        showNextOption = true;
      }
    });

    $(document).on('hostelz:setFormValues', function () {
      updateSearchTitle(formatTile(searchCriteria.startDate, moment(searchCriteria.startDate).add(searchCriteria.nights, 'days')));
    });

    $(document).on('hostelz:searchHeaderDateChanged', function (e, data) {
      if (dateRange.val().localeCompare(data.value) !== 0 ) {
        showNextOption = false;

        dateRange.data('dateRangePicker')
          .setStart(moment(data.startDate).format(dateFormat))
          .setEnd(moment(data.startDate).add(data.nights, 'days').format(dateFormat));
      }
    });

    $(document).on('hostelz:searchMobileLocationChanged hostelz:searchMobileDatesShow', function () {
      wrap.modal('show');
      dateRange.data('dateRangePicker').open();
    });

    function formatTile(start, end) {
      return moment(start).format('D MMM') + ' - ' + moment(end).format('D MMM')
    }

    function updateSearchTitle(value) {
      formTitle.text( value );
    }
  }

  function initLocationMobileSearch(location) {
    var wrap = $('#modalMobileSearchLocation');
    var spinner = wrap.find('.spinner-wrap');

    var autocomlete = wrap.find('.searchLocationMobile').devbridgeAutocomplete({
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
      width: 'flex',
      maxHeight: 'auto',
      appendTo: wrap.find('.location-suggestion'),
      onSearchStart: function () {
        spinner.removeClass('d-none-i');
      },
      onSearchComplete: function () {
        spinner.addClass('d-none-i');
      },
      onSelect: function (suggestion) {
        $(this).val(suggestion.data.query);
        $(this).data('query', suggestion.data.query);
        $(this).data('itemId', suggestion.data.itemId);
        $(this).data('searchURL', suggestion.data.url);
        $(this).data('category', suggestion.data.category);

        wrap.modal('hide');

        $(document).trigger('hostelz:searchMobileLocationChanged', suggestion);

        userSearchHistory(suggestion);
      },
      formatResult: function (suggestion, currentValue) {
        return formatSearchResult(suggestion, currentValue)
      },
    });

    if (location) {
      autocomlete.val(location);
    }

    $(document).trigger('hostelz:searchHeaderLocationMobileDone', locationFieldMobile);

    wrap.find('.search-autocomplete-clear').click(function () {
      autocomlete.val('');
      autocomlete.devbridgeAutocomplete().clear();
      wrap.find('.autocomplete-suggestions').html('');
    });

    $(document).on('hostelz:searchMobileLocationShow', function () {
      wrap.modal('show')
        .on('shown.bs.modal', function (e) {
          $(this).find('.searchLocationMobile')
            .attr('autofocus', 'autofocus')
            .focus();
        });
    });
  }
}

