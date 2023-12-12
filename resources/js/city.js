// cityOptions from cityBottom.blade.php
var cityID = cityOptions.cityId;
var bookingSearchCriteria;
// cityPageSettings cookie values: cityID, doBookingSearch (bool), listingFilters (associative array), resultsOptions (associative array)
var cityPageSettings = triggerHandler('hostelz:getCityPageSettingsCookie', getCityPageSettingsCookie(cityID));
let collapseShow = [];


$('body').on('hostelz:updateListingsSearchResultContent', clickEvents);

$(document).ready(function () {
    if (typeof cityOptions === "undefined") {
        console.log('not city page');
        return;
    }

    initializeCityPage(cityOptions.cityId);
});

function initializeCityPage(cityIdParameter) {
    // cityID = cityIdParameter;

    initSearchLocationField();
    initFilterClick();

    var result = initializeBookingSearchForm(cityOptions.cityName);
    bookingSearchCriteria = result.searchCriteria;

    addListenersForSetCookies()

    updateCheckedFilters(cityPageSettings.listingFilters)

    // * Misc *
    if (cityPageSettings.doBookingSearch) {
        doAvailabilitySearch(false);
        updateFiltersCount(cityPageSettings.page, false)
    } else {
        $(document).trigger('hostelz:skipDateCitySearchInit');
    }

    $(document).on('hostelz:doSearch', function (e, data) {
        bookingSearchGetFormValues(bookingSearchCriteria);
        bookingSearchSetCookie(bookingSearchCriteria);

        $(document).trigger('hostelz:topSearchBeforeSearch');

        cityPageSettings = getCityPageSettingsCookie(cityID);

        if (data.locationField === null || data.locationField.itemId === 0 || data.locationField.itemId === cityID) {
            doAvailabilitySearch();
        } else {
            window.location.href = data.locationField.searchURL !== ''
                ? data.locationField.searchURL
                : '/search?' + $.param({search: data.locationField.query});
        }
    });

    $('.bookingSearchForm .bookingSubmitButton').removeClass('disabled'); /* remove the 'disabled' class now that the form is ready to be submitted */

    // Save the last viewed city / listing so it can be used for the contact form, etc.
    document.cookie = "lastViewed=" + encodeURIComponent(document.location) + ";domain=." + cityOptions.domainName + ";path=/";

    // load asidebar
    $(window).on('load', function () {
        $.get(cityOptions.getCityAd + '/' + cityID, null, function (result) {
            if (result) {
                $(".asidebar-item")
                    .html(result)
                    .parent().removeClass('asidebar-wrap-hide');
            }
        }, 'html');
    });

    loadExploreSection();
}

function addListenersForSetCookies() {
    addListenerForDistrictSort();
    addListenerForDistrictFilter();
}

function loadExploreSection() {
    $.ajax({
        url: cityOptions.exploreURL,
        success: function (htmlContent) {
            $('#loadExploreSection').replaceWith(htmlContent);

            $(document).trigger('hostelz:exploreSectionLoaded');
        },
        error: function (xhr, textStatus, errorThrown) {
            console.warn(textStatus);
        }
    });
}

function initSearchLocationField() {
    $(document).on('hostelz:searchHeaderLocationDone hostelz:searchHeaderLocationMobileDone', function (e, locationFieldHeader) {
        $(locationFieldHeader).data('query', cityOptions.searchData.query);
        $(locationFieldHeader).data('itemId', cityOptions.searchData.itemId);
        $(locationFieldHeader).data('category', cityOptions.searchData.category);
        $(locationFieldHeader).data('searchURL', cityOptions.searchData.searchURL);
    });
}

function canLoadFromStaticServer() {
    if (!$.isEmptyObject(cityPageSettings.listingFilters)) return false;

    if (typeof cityPageSettings.resultsOptions.orderBy != 'undefined' &&
        cityPageSettings.resultsOptions.orderBy.type != 'ratings')
        return false;

    if (typeof cityPageSettings.resultsOptions.resultsPerPage != 'undefined' &&
        cityPageSettings.resultsOptions.resultsPerPage != 'default')
        return false;

    return true;
}

function handleResultsOptionsSelectEvent($selector) {
    cityPageSettings.resultsOptions[$selector.attr('data-selector-name')] = $selector.val();
    updateListingsListUsingFiltersAndSearchCriteria(false, true);
}

function handleChangeCurrencyEvent() {
    bookingSearchGetFormValues(bookingSearchCriteria);
    bookingSearchSetCookie(bookingSearchCriteria);
    updateListingsListUsingFiltersAndSearchCriteria(false, false);
}

function doAvailabilitySearch(bookingSearchButtonClicked) {
    bookingShowWait('#listingsSearchResult', true);

    updateListingsListUsingFiltersAndSearchCriteria(bookingSearchButtonClicked, true);
}

function updateListingsListUsingFiltersAndSearchCriteria(bookingSearchButtonClicked, setCookie, page /* (optional) */) {
    if (typeof page == 'undefined') page = 1;

    $('.cityComments').hide();
    var doBookingSearch = bookingSearchButtonClicked || (cityPageSettings && cityPageSettings.doBookingSearch);

    cityPageSettings = {
        cityID: cityID,
        doBookingSearch: doBookingSearch,
        listingFilters: getSelectedValuesFromFilters('.listingFilters'),
        resultsOptions: cityPageSettings.resultsOptions, // (the resultsOptions variable is already updated)
    };

    cityPageSettings.resultsOptions.pageType = cityOptions.pageType;

    //  use the cityOptions.orderBy if it is set only first time the page is loaded
    if (cityOptions.pageType === 'district' && cityOptions.orderBy) {
        cityPageSettings.resultsOptions.orderBy = cityOptions.orderBy;
        cityOptions.orderBy = null;
    }

    cityPageSettings = $(document).triggerHandler("hostelz:beforeSetCookiesForListingSearch", cityPageSettings)

    if (setCookie) setCityPageSettingsCookie(cityPageSettings);

    if (!doBookingSearch && canLoadFromStaticServer() && page <= 5 /* reasonable number of pages to cache on the static server */) {
        loadListingsList(cityOptions.staticCityListingsListContent + '/' + cityID + '/' + cityPageSettings.resultsOptions.mapMode + '/' + page);
    } else {
        var url = cityOptions.listingsListContent + '/' + cityID + "?optionsData=" + getCityURLOptionsData(doBookingSearch);

        if (typeof page != 'undefined') url = url + '&page=' + page;

        loadListingsList(url);
    }
}

function getCityURLOptionsData(doBookingSearch) {
    var dataToSubmit = {
        resultsOptions: cityPageSettings.resultsOptions
    };

    if (doBookingSearch) {
        dataToSubmit.bookingSearchCriteria = bookingSearchCriteria;
    }

    if (!$.isEmptyObject(cityPageSettings.listingFilters)) {
        dataToSubmit.listingFilters = cityPageSettings.listingFilters;
    }

    return encodeURIComponent(JSON.stringify(dataToSubmit));
}


function pushCollapseShowValues(item) {
    collapseShow = []
    collapseShow.push(item)
}

function updateCollapseShowValues(search) {
    if (search) {
        collapseShow.forEach(item => $(item).removeClass('show'))
        collapseShow = []
        return
    }

    let item = collapseShow[0]
    $(item)?.addClass('show')
}

var loadListingsAjaxRequest = null;

function loadListingsList(url, minimumSecondsToDelay /* optional */) {
    // We have to manually set the doAfterMapScriptIsLoaded() function to null so it doesn't exist unless the new listings list re-defines it.
    doAfterMapScriptIsLoaded = null;

    if (loadListingsAjaxRequest != null) {
        // Abort any pending ajax requests
        loadListingsAjaxRequest.abort();
        loadListingsAjaxRequest = null;
    }

    if (minimumSecondsToDelay) var startTime = new Date();

    loadListingsAjaxRequest = $.ajax({
        url: url,
        success: function (htmlContent) {
            if (minimumSecondsToDelay) {
                // Make sure that it takes at least this much time to load
                // so when they click the search button but the page was already cached it's more obvious that it did something.
                var timeLeftToWait = minimumSecondsToDelay * 1000 - ((new Date()) - startTime);
                if (timeLeftToWait > 0) {
                    setTimeout(function () {
                        updateListingsSearchResultContent(htmlContent);
                        // $('body').trigger('hostelz:updateListingsSearchResultContent');
                    }, timeLeftToWait);
                    return;
                }
            }
            updateListingsSearchResultContent(htmlContent);

            // $('body').trigger('hostelz:updateListingsSearchResultContent');
        },
        error: function (xhr, textStatus, errorThrown) {
            if (textStatus != 'abort') generalLoadFailedErrorMessage('#listingsSearchResult');
            console.warn(textStatus);
        }
    });
}

function updateAvailableListingsCount(htmlContent) {
    let submitButton = $('#searchFiltersSubmit span');

    if ($.isEmptyObject(cityPageSettings.listingFilters)) {
        submitButton.text('show results')
        return
    }

    let hostelsCount = $(htmlContent).find('.hostel-count').val();

    if (!hostelsCount >= 1) {
        submitButton.text('no available places')
        return
    }

    let resultCount = '(' + hostelsCount + ')';

    submitButton.text('show results ' + resultCount)
}

function updateListingsSearchResultContent(htmlContent) {
    $('#listingsSearchResult').html(htmlContent);

    //  update the page title
    if ($(htmlContent).filter('.js-title-data').length > 0) {
        $('.title-section').html($('.js-title-data').html());
        $('body').find('.hero-description').show();
    }

    addFilterButtonHighlight()

    /* Save the listingsList to local storage so we can later use it on the listing pages for the previous/next buttons */
    var listingURLs = $('a.listingLink').map(function () {
        return this.href;
    }).get();
    localStorage.setItem('listingsList', JSON.stringify({'cityURL': window.location.href, 'listingURLs': listingURLs}));

    refreshMap();

    $('.cityComments').show();

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

    $('.no-available-another-date').click(function () {
        $('#header-search-result__dates').click();
        return false;
    })

    initBSSelects();
}

function clickEvents() {
    $('.resultsOptions > select').change(function (e) {
        handleResultsOptionsSelectEvent($(this));
    });

    $('.setMapMode').click(function (event) {
        event.preventDefault();
        cityPageSettings.resultsOptions['mapMode'] = $(this).attr('data-map-mode');

        bookingShowWait('#listingsSearchResult', true);
        updateListingsListUsingFiltersAndSearchCriteria(false, true);
    });

    $('.setListFormat').click(function (event) {
        event.preventDefault();
        cityPageSettings.resultsOptions['listFormat'] = $(this).attr('data-list-format');
        updateListingsListUsingFiltersAndSearchCriteria(false, true);
    });

    $('#listingsSearchResult .pagination a').click(function (event) {
        event.preventDefault();
        var page = $(this).attr('href').replace(/^\D+/g, '');
        updateListingsListUsingFiltersAndSearchCriteria(false, true, page);
        bookingShowWait('#listingsSearchResult', true);
        scrollToTopListingList();
    });

    initSortBy()
    initFilters()
}

function scrollToTopListingList(e) {
    $([document.documentElement, document.body]).animate({
        scrollTop: $("#listingsSearchResult").offset().top
    }, 700);
}


/* Map */

var mapScriptIsLoaded = false;
var map;
var listingIcon, listingIconBlue;

var resizeTimer;
$(window).on('resize', function () {
    // This makes sure we only get one event after resizing is done
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
        refreshMap();
    }, 250);
});

// Callback for after Google Maps API is loaded.

function mapScriptLoaded() {
    mapScriptIsLoaded = true;
    // Note: There's a slight chance at this moment refreshMap() could also get called by updateListingsSearchResultContent() for example,
    // but that's ok, it's ok if it gets refreshed twice.
    refreshMap();
}

function refreshMap() {
    if (!mapScriptIsLoaded) return; /* the map will get updated later by mapScriptLoaded() */

    // (doAfterMapScriptIsLoaded() won't exist if the listings list hasn't yet loaded, there are no mappable listings, or if the map is closed.)
    if (typeof doAfterMapScriptIsLoaded == 'function') doAfterMapScriptIsLoaded();
}

function addPoiMarker(latitude, longitude, name) {
    var poiIcon = {
        url: '/images/mapMarker-poi-yellow.png',
        size: new google.maps.Size(cityOptions.mapMarkerWidth, cityOptions.mapMarkerHeight),
        scaledSize: new google.maps.Size(
            cityOptions.mapMarkerWidth * cityOptions.scalePoiIcon,
            cityOptions.mapMarkerHeight * cityOptions.scalePoiIcon
        ),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(
            cityOptions.mapMarkerWidth * cityOptions.scalePoiIcon / 2,
            cityOptions.mapMarkerHeight * cityOptions.scalePoiIcon / 2
        )
    };

    // idle event is used so there's enough time for them to see the map to see the animation of adding the marker
    // (also otherwise extendMapBoundsToLatitudeLongitude() may fail because the map bounds aren't yet known)

    var listenerHandle = google.maps.event.addListener(map, 'idle', function () {
        extendMapBoundsToLatitudeLongitude(map, latitude, longitude);

        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(latitude, longitude),
            anchorPoint: new google.maps.Point(0, -(listingIcon.scaledSize.height / 2)), // where the InfoWindow points to
            map: map,
            zIndex: google.maps.Marker.MAX_ZINDEX + 2, // (make it's in front of city and listing markers)
            icon: poiIcon,
            animation: google.maps.Animation.DROP,
            title: name,
        });

        var infoWindow = new google.maps.InfoWindow({
            content: name
        });

        marker.addListener('click', function () {
            infoWindow.open(map, marker);
        });

        google.maps.event.removeListener(listenerHandle); // remove self (so this event only fires once)
    });
}

function updateMapMarkers(thisCityID, mapBounds, mapPoints) {
    map = createMap('mapCanvas', mapBounds);

    // * Marker Icons *
    listingIcon = {
        url: cityOptions.mapMarker,
        size: new google.maps.Size(cityOptions.mapMarkerWidth, cityOptions.mapMarkerHeight),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(
            cityOptions.mapMarkerWidth / 2,
            cityOptions.mapMarkerHeight
        )
    };

    listingIconBlue = {
        url: cityOptions.mapMarkerBlue,
        size: new google.maps.Size(cityOptions.mapMarkerWidth, cityOptions.mapMarkerHeight),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(
            cityOptions.mapMarkerWidth / 2,
            cityOptions.mapMarkerHeight
        )
    }

    const listingIconHighlighted = $.extend({}, listingIcon, {url: cityOptions.mapMarkerHostelHighlighted});
    const listingIconBlueHighlighted = $.extend({}, listingIconBlue, {url: cityOptions.mapMarkerBlueHostelHighlighted});

    scaleIconToZoomLevel(map, listingIcon, 19, 4, 0.5, 1.0);
    scaleIconToZoomLevel(map, listingIconBlue, 19, 4, 0.5, 1.0);
    scaleIconToZoomLevel(map, listingIconHighlighted, 19, 4, 0.5, 1.0);
    scaleIconToZoomLevel(map, listingIconBlueHighlighted, 19, 4, 0.5, 1.0);

    // * Markers / InfoWindow *

    var infoWindow = new google.maps.InfoWindow();

    $.each(mapPoints, function (i, mapPoint) {
        const propertyType = mapPoint[6]
        const hostelName = mapPoint[3]
        let rating = ' - ' + mapPoint[5]

        let icon = propertyType === 'Hostel' ? listingIcon : listingIconBlue
        let highlightedIcon = propertyType === 'Hostel' ? listingIconHighlighted : listingIconBlueHighlighted

        const marker = new google.maps.Marker({
            position: new google.maps.LatLng(mapPoint[0], mapPoint[1]),
            map: map,
            zIndex: google.maps.Marker.MAX_ZINDEX + 1, // (make sure listing markers are in front of city markers)
            icon: icon,
            title: hostelName
        });

        marker.addListener('click', function () {
            if (rating.includes('0.0')) {
                rating = ''
            }

            infoWindow.setContent('<a href="' + mapPoint[4] + '" target="_blank">' + hostelName + rating + '</a>');
            infoWindow.open(map, marker);
        });

        google.maps.event.addListener(marker, 'mouseover', function () {
            marker.setIcon(highlightedIcon);
        });

        google.maps.event.addListener(marker, 'mouseout', function () {
            marker.setIcon(icon);
        });
    });

    // * Dynamically Loaded City Markers *

    // ('idle' is the event that is triggered *after* the map has been moved or zoomed)
    map.addListener('idle', function () {
        var propertyTypes = getSelectedValuesFromDropdownMenu($('.listingFilters [data-dropdown-name="propertyType"]'), 'multiple');
        var isFilteringForOnlyHostels = (JSON.stringify(propertyTypes) == JSON.stringify(["Hostel"]));

        dynamiclyLoadededCityMarkersRefresh(map, 'exceptCityIDs=' + thisCityID +
            (isFilteringForOnlyHostels ? '&hostelsOnly=1' : ''), 12);
    });

    dynamiclyLoadededCityMarkersInitialize(false);
}

/*  https://johannburkard.de/blog/programming/javascript/highlight-javascript-text-higlighting-jquery-plugin.html */
jQuery.fn.highlight = function (pat) {
    function innerHighlight(node, pat) {
        var skip = 0;
        if (node.nodeType == 3) {
            var pos = node.data.toUpperCase().indexOf(pat);
            pos -= (node.data.substr(0, pos).toUpperCase().length - node.data.substr(0, pos).length);
            if (pos >= 0) {
                var spannode = document.createElement('span');
                spannode.className = 'highlight';
                var middlebit = node.splitText(pos);
                var endbit = middlebit.splitText(pat.length);
                var middleclone = middlebit.cloneNode(true);
                spannode.appendChild(middleclone);
                middlebit.parentNode.replaceChild(spannode, middlebit);
                skip = 1;
            }
        } else if (node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
            for (var i = 0; i < node.childNodes.length; ++i) {
                i += innerHighlight(node.childNodes[i], pat);
            }
        }
        return skip;
    }

    return this.length && pat && pat.length ? this.each(function () {
        innerHighlight(this, pat.toUpperCase());
    }) : this;
};

jQuery.fn.removeHighlight = function () {
    return this.find("span.highlight").each(function () {
        var parent = this.parentNode;
        parent.replaceChild(this.firstChild, this);
        parent.normalize();
    }).end();
};
