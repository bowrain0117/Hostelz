function initSearchListing (searchCriteria) {
  var form = $('.bookingSearchFormSecond');
  if (!form.length) {
    return;
  }

  disableCloseOnDropdownClick();

  initBookingSearchDate(searchCriteria.startDate, searchCriteria.nights);
  initSearchRoomType();
  initSearchGuests();
  initSearchGroupType();
  initSearchGroupAgeRanges();
  initSearchPrivateRooms();


  function initSearchRoomType() {
    var inputs = form.find('[name="searchCriteriaSecond[roomType]"]');

    inputs.change(function () {
      var value = $(this).val();
      $(document).trigger('hostelz:searchSecondRoomTypeChanged', value);
    });

    $(document).on('hostelz:setFormValues', function () {
      inputs.filter('[value="' + searchCriteria.roomType + '"]').prop('checked', true);
    });

    $(document).on('hostelz:searchRoomTypeChanged', function (e, value) {
      inputs.filter('[value="' + value + '"]').prop('checked', true);
    });
  }

  $(document).on('hostelz:setFormValues', function () {
    form.find('.bookingSearchRoomType input').change(function () {
      $(document).trigger('hostelz:searchSecondDoSearch');
    });
  });

  function initSearchGuests() {
    var wrap = $('#searchGuestsSecond');
    var input = $('#bookingSearchPeopleSecond');
    var formTitle = wrap.find('button');

    input.change(function () {
      updateSearchTitle(input.val());

      $(document).trigger('hostelz:searchSecondGuestsChanged', input.val());

      $(document).trigger('hostelz:searchSecondDoSearch');
    });

    $(document).on('hostelz:setFormValues', function () {
      input.val(searchCriteria.people);
      updateSearchTitle(searchCriteria.people);
    });

    $(document).on('hostelz:searchHeaderGuestsChanged', function (e, value) {
      if (input.val() !== value) {
        input.val(value);
        updateSearchTitle(value);
      }
    });

    function updateSearchTitle(numbers) {
      var data = numbers + ' ' + (numbers > 1 ? 'guests' : 'guest');
      formTitle.text( data );
    }
  }

  function initSearchPrivateRooms() {
    var wrap = $('.bookingSearchRooms');
    var input = $('#bookingSearchRoomsSecond');
    var formTitle = wrap.find('button');

    input.change(function () {
      updateSearchTitle(input.val());

      $(document).trigger('hostelz:searchSecondPrivateRoomsChanged', input.val());

      $(document).trigger('hostelz:searchSecondDoSearch');
    });

    $(document).on('hostelz:setFormValues', function () {
      input.val(searchCriteria.rooms);
      toggle(searchCriteria.roomType);
      updateSearchTitle(searchCriteria.rooms);
    });

    $(document).on('hostelz:searchRoomTypeChanged', function (e, value) {
      toggle(value);
    });

    $(document).on('hostelz:searchHeaderRoomsChanged', function (e, value) {
      if (input.val() !== value) {
        input.val(value);
        updateSearchTitle(value);
      }
    });

    function updateSearchTitle(numbers) {
      var data = numbers + ' ' + (numbers > 1 ? 'rooms' : 'room');
      formTitle.text( data );
    }

    function toggle(value) {
      value === 'dorm'
        ? wrap.hide()
        : wrap.show()
    }
  }

  function initBookingSearchDate(startDate, nights) {
    var searchDateContent = $('#searchDateContent ._date');

    var dateRange = $('#searchDate');
    var dateFormat = 'YYYY-MM-DD';

    var dateRangeConfig = {
      inline:true,
      autoClose: false,
      format: dateFormat,
      separator: ' - ',
      startDate: moment().format(dateFormat),
      minDate: moment().format(dateFormat),
      selectForward: true,
      extraClass: 'searchDateWrap',
      container: '.datepicker-modal',
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

    dateRange.data('startDate', startDate);
    dateRange.data('night', nights);

    var formatted = moment(startDate).format('Do MMMM YYYY') + ', ' +  nights + ' ' + (nights > 1 ? 'nights' : 'night');

    var searchDateModal = $('#searchDateModal');

    searchDateContent.text(formatted);

    dateRange.bind('datepicker-change', function (event, obj) {
      var startDate = moment(obj.date1).format(dateFormat);
      var nights = parseInt(moment(obj.date2).diff(moment(obj.date1), 'days'));

      if (nights === 0) {
        dateRange.data('dateRangePicker').setEnd(moment(obj.date1).add(1,'days').format(dateFormat));
        nights = 1;
      }

      dateRange.data('startDate', startDate);
      dateRange.data('night', nights);

      var formatted = moment(obj.date1).format('Do MMMM YYYY') + ', ' +  nights + ' ' + (nights > 1 ? 'nights' : 'night');
      searchDateContent.text(formatted);

      $(document).trigger('hostelz:searchSecondDateChanged', {startDate: startDate, nights: nights});

      $(document).trigger('hostelz:searchSecondDoSearch');

    }).bind('datepicker-closed', function () {
      searchDateModal.modal('hide');
    });

    $(document).on('hostelz:searchHeaderDateChanged', function (e, data) {
      if (data.startDate.localeCompare(dateRange.data('startDate')) !== 0 || data.nights !== parseInt(dateRange.data('night')) ) {
        dateRange.data('dateRangePicker').setStart(moment(data.startDate).format(dateFormat));
        dateRange.data('dateRangePicker').setEnd(moment(data.startDate).add(data.nights, 'days').format(dateFormat));
      }
    });

    searchDateContent.click(function () {
      searchDateModal.modal('show')
        .on('shown.bs.modal', function (e) {
          dateRange.data('dateRangePicker').open();
        })
        .on('hidden.bs.modal', function (e) {
          dateRange.data('dateRangePicker').close();
        })
    });
  }

  function initSearchGroupType() {
    var wrap = $('.bookingSearchGroupTypeSecondWrap')
    var field = wrap.find('select');

    field.val(searchCriteria.groupType);

    field.change(function () {
      $(document).trigger('hostelz:searchSecondGroupTypeChanged', field.val());
    });

    $(document).on('hostelz:searchHeaderGroupTypeChanged', function (e, value) {
      field.val(value);
    });
  }

  function initSearchGroupAgeRanges() {
    var wrap = $('.bookingSearchAgeRangesSecondWrap')
    var inputs = wrap.find('input');

    checkValues(searchCriteria.groupAgeRanges);

    inputs.change(function (e) {
      $(document).trigger('hostelz:searchSecondGroupAgeRangesChanged', getCheckedValues());
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

  function disableCloseOnDropdownClick() {
    $(document).on('click', '.bookingSearchFormSecond .dropdown-menu', function (e) {
      e.stopPropagation();
    });
  }
}
