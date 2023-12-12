/*
* indexOptions - from index.blade.php
* */
var searchCriteria;

$(document).ready (function () {
  initializeIndexPage();
});

function initializeIndexPage() {
  if (typeof(initializeBookingSearchForm) !== "function") {
    return;
  }

  $(document).on('hostelz:searchInit', function (e, searchCriteria) {
    initHeroSearch(searchCriteria);
  })

  var result = initializeBookingSearchForm();
  searchCriteria = result.searchCriteria;

  $(document).on('hostelz:doSearch', function (e, data) {
    bookingSearchGetFormValues(searchCriteria);
    bookingSearchSetCookie(searchCriteria);

    if (data.locationField === null) {
      $(document).trigger('hostelz:topSearchEmptyLocation');

      return true;
    }

    window.location.href = data.locationField && data.locationField.searchURL !== ''
      ? data.locationField.searchURL
      : '/search?' + $.param( { search: data.locationField.query } );
  });

  function initHeroSearch(searchCriteria) {
    var form = $('#heroSearch');
    if (form.length === 0) {
      return;
    }
    var locationField = $('#header-search-form__location input[name="location"]');

    initSubmit();

    initShowForm();

    initLocationHeroSearch();
    initDatesHeroSearch(searchCriteria.startDate, searchCriteria.nights);
    initSearchRoomType();
    initSearchGuests(searchCriteria);
    initSearchRooms(searchCriteria);

    function initShowForm() {
      $('#searchIndexMobileButtonWrap .searchIndexMobileButton').click(function () {
        $(document).trigger('hostelz:searchMobileLocationShow');
      });
    }

    function initSubmit() {
      form.submit(function (e) {
        e.preventDefault();

        turnDoBookingSearchForCityTo(locationField.data('itemId'), true);

        doSearch(locationField);
      });
    }

    function initSearchGuests(searchCriteria) {
      var input = $('#bookingSearchPeopleHero');

      input.change(function () {
        $(document).trigger('hostelz:searchHeroGuestsChanged', input.val());
      });

      $(document).on('hostelz:setFormValues', function () {
        input.val(searchCriteria.people);
      });

      $(document).on('hostelz:searchHeaderGuestsChanged', function (e, value) {
        if (input.val() !== value) {
          input.val(value);
        }
      });
    }

    function initSearchRooms(searchCriteria) {
      var input = $('#bookingSearchRooms');

      input.change(function () {
        $(document).trigger('hostelz:searchHeroRoomsChanged', input.val());
      });

      $(document).on('hostelz:setFormValues', function () {
        input.val(searchCriteria.rooms);
      });

      $(document).on('hostelz:searchHeaderRoomsChanged', function (e, value) {
        if (input.val() !== value) {
          input.val(value);
        }
      });
    }

    function initSearchRoomType() {
      var inputs = form.find('[name="searchCriteriaHero[roomType]"]');

      inputs.change(function () {
        var value = $(this).val();
        $(document).trigger('hostelz:searchHeroRoomTypeChanged', value);
      });

      $(document).on('hostelz:setFormValues', function () {
        inputs.filter('[value="' + searchCriteria.roomType + '"]').prop('checked', true);
      });

      $(document).on('hostelz:searchRoomTypeChanged', function (e, value) {
        inputs.filter('[value="' + value + '"]').prop('checked', true);
      });
    }

    function initDatesHeroSearch(startDate, nights) {
      const dateRange = $('#searchDateHero');
      const dateRangeTitle = $('#searchDateHeroTitle');
      const dateShowFormat = 'D MMM';
      const dateFormat = 'YYYY-MM-DD';
      const searchDateModal = $('#searchDateModalHero');

      const dateRangeConfig = {
        inline: true,
        autoClose: false,
        format: dateFormat,
        separator: ' - ',
        startDate: moment().format(dateFormat),
        // minDate: 1,
        maxDays: 31,
        selectForward: true,
        extraClass: 'searchDateWrap',
        container: '.datepicker-modal',
        language: 'auto',
        showTopbar: false,
        width: 415,
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
              name: '<span id="header-search-date-skip" type="button" class="btn btn-sm btn-primary">skip dates</span>',
              dates : function() {
                  if (isLocationCategoryForCity(locationField.data('category')) && locationField.data('itemId') ) {
                    turnDoBookingSearchForCityTo(locationField.data('itemId'), false);
                  }

                  doSearch(locationField);

                  dateRange.data('dateRangePicker').close();
              }
            },
          ],
        hoveringTooltip: function(days, startTime, hoveringTime)
        {
          return getDatesTooltip(days);
        },
        setValue: function (string, start, end) {
          if (!$(this).attr('readonly') && !$(this).is(':disabled') && string != $(this).val()) {
            $(this).val(string);

            updateSearchTitle(moment(start).format(dateShowFormat) + ' - ' + moment(end).format(dateShowFormat))
          }
        }
      };

      dateRange.dateRangePicker(dateRangeConfig)
        .bind('datepicker-change', function (event, obj) {
          const startDate = moment(obj.date1).format(dateFormat);
          let nights = parseInt(moment(obj.date2).diff(moment(obj.date1), 'days'));

          if (nights === 0) {
            dateRange.data('dateRangePicker').setEnd(moment(obj.date1).add(1,'days').format(dateFormat));
            nights = 1;
          }

          dateField.val(startDate);
          nightField.val(nights);

          $(document).trigger('hostelz:searchHeroDateChanged', {startDate: startDate, nights: nights});
        })
        .bind('datepicker-closed', function () {
          searchDateModal.modal('hide');
        });

      dateRange.data('dateRangePicker').setStart(moment(startDate).format(dateFormat));
      dateRange.data('dateRangePicker').setEnd(moment(startDate).add(nights, 'days').format(dateFormat));

      const dateField = $('#bookingSearchDateHero');
      const nightField = $('#bookingSearchNightsHero');

      dateField.val(startDate);
      nightField.val(nights);

      $(document).on('hostelz:searchHeaderDateChanged', function (e, data) {
        if (data.startDate.localeCompare(dateField.val()) !== 0 || data.nights !== parseInt(nightField.val())) {
          dateRange.data('dateRangePicker').setStart(moment(data.startDate).format(dateFormat));
          dateRange.data('dateRangePicker').setEnd(moment(data.startDate).add(data.nights, 'days').format(dateFormat));
        }
      });

      function updateSearchTitle(value) {
        dateRangeTitle.val( value );
      }

      dateRangeTitle.click(function () {
        searchDateModal.modal('show')
          .on('shown.bs.modal', function (e) {
            dateRange.data('dateRangePicker').open();
          })
          .on('hidden.bs.modal', function (e) {
            dateRange.data('dateRangePicker').close();
          })
      });
    }

    function initLocationHeroSearch() {
      var input = $('.websiteIndexSearch');
      var errorClass = 'is-invalid';

      input.click(function () {
        if (form.hasClass('heroSearchSmall')) {
          form.removeClass('heroSearchSmall');
        }

        return true;
      });

      var spinner = input.next('.spinner-wrap');

      let searchDateModal = $('#searchDateModalHero');
      let dateRange = $('#searchDateHero');

      var autocomlete = input.devbridgeAutocomplete({
        serviceUrl: globalOptions.routes.searchAutocomplete,
        paramName: 's',
        minChars: 0,
        groupBy: 'category',
        preserveInput: true,
        triggerSelectOnValidInput: false,
        deferRequestBy: 100,
        zIndex: 1100,
        appendTo: $('#locationHero'),
        onSearchStart: function () {
          spinner.removeClass('d-none-i');
        },
        onSearchComplete: function (query, suggestions) {

          spinner.addClass('d-none-i');

          $(this).removeClass(errorClass);
        },
        onSelect: function (suggestion) {
          userSearchHistory(suggestion);

          setInputData(suggestion);

          $(document).trigger('hostelz:searchHeroLocationChanged', suggestion);

          searchDateModal.modal('show')
            .on('shown.bs.modal', function (e) {
              dateRange.data('dateRangePicker').open();
            })
            .on('hidden.bs.modal', function (e) {
              dateRange.data('dateRangePicker').close();
            })
        },
        formatResult: function (suggestion, currentValue) {
          return formatSearchResult(suggestion, currentValue)
        },
      });

      $(document).on('hostelz:searchMobileLocationChanged', function (e, suggestion) {
        setInputData(suggestion);
      });

      //  show error message
      $(document).on('hostelz:topSearchEmptyLocation', function () {
        autocomlete.focus().addClass(errorClass);
      });

      function setInputData(suggestion) {
        autocomlete.val(suggestion.data.query);
      }
    }
  }
}
