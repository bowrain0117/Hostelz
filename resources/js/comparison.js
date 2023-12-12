$(document).ready(function() {
    if (typeof(listingsBookingSearchUrl) === 'undefined') {
        initComparisonButtonClick()
        initializeComparePage()
        return
    }

    let url = window.location.pathname.split('/')
    let listingsId = url.at(-1)

    initializeComparePage(listingsId)
});

function initializeComparePage(listingsId) {
    let result = initializeBookingSearchForm();

    searchCriteria = result.searchCriteria;

    if (listingsId) {
        bookingUpdate(listingsId)
    }

    $(document).on('hostelz:doSearch', function (e, data) {
        bookingSearchGetFormValues(searchCriteria);
        bookingSearchSetCookie(searchCriteria);

        $(document).trigger('hostelz:topSearchBeforeSearch');

        if ( !data.locationField || data.locationField.itemId === 0) {
            bookingUpdate(listingsId);
        } else {
            window.location.href = data.locationField.searchURL !== ''
                ? data.locationField.searchURL
                : '/search?' + $.param( { search: data.locationField.query } );
        }
    });
}

function bookingUpdate(listingsId) {
    bookingShowWait('#compare', false);

    let compare = $('#compare');
    compare.addClass('container');

    $.ajax({
        url: listingsBookingSearchUrl + '/' + listingsId + "?" + $.param(searchCriteria),
        success : function(htmlContent) {
            compare.removeClass('container');
            compare.html(htmlContent);

            $('.switchToDormSearch').click(function () {
                $(document).trigger('hostelz:noAvailableDatesRoomTypeChanged', 'dorm');

                $(document).trigger('hostelz:noAvailableDatesSearch');

                return false;
            });

            $('.switchToPrivateSearch').click(function () {
                $(document).trigger('hostelz:noAvailableDatesRoomTypeChanged', 'private');

                $(document).trigger('hostelz:noAvailableDatesSearch');

                return false;
            });

            $(document).trigger('hostelz:bookingUpdated');
        },
        error : function(xhr, textStatus, errorThrown ) { bookingGeneralErrorMessage('#compare'); }
    });
}

function initComparisonButtonClick() {
    const body = $('body');

    body.on('click', '.js-open-search-location', function (e){
        e.preventDefault();

        $('#header-search-result__location').addClass('show')
    })
}

function displayMap(mapBounds, mapPoints)
{
    let map = createMap('mapCanvas', mapBounds);
    // * Marker Icons *
    let hostelIcon = {
        url: citiesOptions.mapMarker.mapMarkerBlue,
        size: new google.maps.Size(citiesOptions.mapMarker.width * 0.5, citiesOptions.mapMarker.height * 0.5),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(citiesOptions.mapMarker.width * 0.5, citiesOptions.mapMarker.height)
    };

    let hostelIconHighlighted = $.extend({ }, hostelIcon, { url: citiesOptions.mapMarker.mapMarkerBlueHostelHighlighted });

    scaleIconToZoomLevel(map, hostelIcon, 19, 4, 0.5, 1.0);
    scaleIconToZoomLevel(map, hostelIconHighlighted, 19, 4, 0.5, 1.0);

    // * Markers / InfoWindow *

    $.each(mapPoints, function(i, mapPoint) {
        let marker = new google.maps.Marker({
            position: new google.maps.LatLng(mapPoint[0], mapPoint[1]),
            anchorPoint: new google.maps.Point(0, -(hostelIcon.scaledSize.height / 2)), // where the InfoWindow points to
            zIndex: google.maps.Marker.MAX_ZINDEX + 1, // (make sure this page's city markers are in front of dynamically loaded city markers)
            map: map,
            icon: hostelIcon,
            title: mapPoint[3] + ' - ' + mapPoint[4] + ', ' + mapPoint[5]
        });

        marker.addListener('click', function() {
            window.open($('.comparison-card a[data-hostel-id="' + mapPoint[2] + '"]').attr('href'), '_blank')
        });

        google.maps.event.addListener(marker, 'mouseover', function() {
            marker.setIcon(hostelIconHighlighted);
        });
        google.maps.event.addListener(marker, 'mouseout', function() {
            marker.setIcon(hostelIcon);
        });
    });
}