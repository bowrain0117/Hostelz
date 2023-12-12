var searchCriteria;

initializeArticlesPage();

function initializeArticlesPage()
{
  initHeaderSearch();

  function initHeaderSearch() {
    var result = initializeBookingSearchForm();
    searchCriteria = result.searchCriteria;

    $('.bookingSearchForm .bookingSubmitButton').removeClass('disabled'); /* remove the 'disabled' class now that the form is ready to be submitted */

    $(document).on('hostelz:doSearch', function (e, data) {
      bookingSearchGetFormValues(searchCriteria);
      bookingSearchSetCookie(searchCriteria);

      window.location.href = data.locationField.searchURL !== ''
        ? data.locationField.searchURL
        : '/search?' + $.param( { search: data.locationField.query } );

    });
  }
}

