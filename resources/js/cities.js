var searchCriteria;

function initializeCitiesPage(countryID)
{
  $(document).ready(function() {

    /* Map */
    $.Topic('mapSectionOpenClose').subscribe(mapOpenedOrClosed);
    if ($('#mapContentBox').css('display') != 'none') mapOpenedOrClosed('opened');

    /* Make the title click-able for staff to edit the cityInfo */
    if (typeof loginInfoCookie.permissions != 'undefined' && loginInfoCookie.permissions.indexOf('admin') != -1) {
      $('h1').html($('<a style="color:#666">').attr('href', citiesOptions.staffCountryInfos + '/'+countryID).html($('h1').html()));
    }
  });

  setLocationData(citiesOptions.search);

  initHeaderSearch(citiesOptions.search.query);

  function setLocationData(data) {
    var location = $('.searchLocation');

    location.val(data.value);
    location.data('query', data.query);
    location.data('itemId', data.itemId);
    location.data('searchURL', data.url);
  }
}

function initHeaderSearch(location) {
  var result = initializeBookingSearchForm(location);
  searchCriteria = result.searchCriteria;

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

function showHostelsOnlyMapPoints(isContinentPage)
{
  if (isContinentPage) return

  $.each(nonHostelCityMarkers, function(i, marker) {
    marker.setVisible(false);
  });

  setDynamiclyLoadededCityMarkerRefreshHandler(true, true);
}

function showAllAccommodationsMapPoints(isContinentPage)
{
  if (isContinentPage) return

  $.each(nonHostelCityMarkers, function(i, marker) {
    marker.setVisible(true);
  });

  setDynamiclyLoadededCityMarkerRefreshHandler(false, true);
}

/* Map */

var map;
var nonHostelCityMarkers;
var mapScriptDownloadStarted = false;
var dynamiclyLoadededCityMarkerQueryString;
var dynamiclyLoadededCityMarkerEventHandler = null;

function mapOpenedOrClosed(status)
{
  if (status == 'closed') return; // nothing to do, we only care if it was opened
  if (!mapScriptDownloadStarted) {
    mapScriptDownloadStarted = true;
    $.getScript(citiesOptions.googleMapUrl)
  }
}

function displayMap(mapBounds, mapPoints, country, region, cityGroup)
{
  map = createMap('mapCanvas', mapBounds);

  // * Marker Icons *

  cityIcon = {
    url: citiesOptions.mapMarker.mapMarkerCities,
    size: new google.maps.Size(citiesOptions.mapMarker.width * 0.5, citiesOptions.mapMarker.height * 0.5),
    origin: new google.maps.Point(0, 0),
    anchor: new google.maps.Point(citiesOptions.mapMarker.width * 0.5, citiesOptions.mapMarker.height)
  };

  cityIconHighlighted = $.extend({ }, cityIcon, { url: citiesOptions.mapMarker.mapMarkerCitiesHighlighted });

  scaleIconToZoomLevel(map, cityIcon, 19, 4, 0.5, 1.0);
  scaleIconToZoomLevel(map, cityIconHighlighted, 19, 4, 0.5, 1.0);

  // * Markers / InfoWindow *

  var hostelCityMarkers = [ ];
  nonHostelCityMarkers = [ ];

  $.each(mapPoints, function(i, mapPoint) {
    var marker = new google.maps.Marker({
      position: new google.maps.LatLng(mapPoint[0],mapPoint[1]),
      anchorPoint: new google.maps.Point(0, -(cityIcon.scaledSize.height/2)), // where the InfoWindow points to
      zIndex: google.maps.Marker.MAX_ZINDEX + 1, // (make sure this page's city markers are in front of dynamically loaded city markers)
      map: map,
      icon: cityIcon,
      title: mapPoint[3]
    });

    if (mapPoint[4])
      hostelCityMarkers.push(marker);
    else
      nonHostelCityMarkers.push(marker);

    marker.addListener('click', function() {
      window.location.href = $('.citiesList a[data-city-id="'+mapPoint[2]+'"]').attr('href');
    });
    google.maps.event.addListener(marker, 'mouseover', function() {
      marker.setIcon(cityIconHighlighted);
    });
    google.maps.event.addListener(marker, 'mouseout', function() {
      marker.setIcon(cityIcon);
    });
  });

  map.addListener('zoom_changed', function() {
    scaleIconToZoomLevel(map, cityIcon, 11, 4, 0.5, 1.0);
    scaleIconToZoomLevel(map, cityIconHighlighted, 11, 4, 0.5, 1.0);
    $.each(hostelCityMarkers, function(i, marker) {
      marker.setIcon(cityIcon);
      marker.anchorPoint = new google.maps.Point(0, -(cityIcon.scaledSize.height/2)); // where the InfoWindow points to
    });
    $.each(nonHostelCityMarkers, function(i, marker) {
      marker.setIcon(cityIcon);
      marker.anchorPoint = new google.maps.Point(0, -(cityIcon.scaledSize.height/2)); // where the InfoWindow points to
    });
  });

  // * Dynamically Loaded City Markers *

  dynamiclyLoadededCityMarkerQueryString = 'exceptCountry='+country+'&exceptRegion='+region+'&exceptCityGroup='+cityGroup;
  setDynamiclyLoadededCityMarkerRefreshHandler(false, false);

  dynamiclyLoadededCityMarkersInitialize(true);
}


function setDynamiclyLoadededCityMarkerRefreshHandler(hostelsOnly, refreshNow)
{
  var doRefresh = function () {
    dynamiclyLoadededCityMarkersRefresh(map, dynamiclyLoadededCityMarkerQueryString+'&hostelsOnly='+(hostelsOnly ? 1 : 0), 18);
  };

  if (dynamiclyLoadededCityMarkerEventHandler) dynamiclyLoadededCityMarkerEventHandler.remove();

  // ('idle' is the event that is triggered *after* the map has been moved or zoomed)
  dynamiclyLoadededCityMarkerEventHandler = map.addListener('idle', function () {
    doRefresh();
  });

  if (refreshNow) doRefresh();
}