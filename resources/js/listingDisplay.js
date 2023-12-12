var listingID;
var searchCriteria;

function initializeListingDisplayPage(listingIdParameter, needToGetDynamicDataForListing, lastUpdatedForCurrentlyDisplayedListing)
{
  listingID = listingIdParameter;
  if (!showPreviousAndNext()) $('.addressCityLink').show();
  if (needToGetDynamicDataForListing) getDynamicDataForListing(listingID, lastUpdatedForCurrentlyDisplayedListing);

  if (typeof(initializeBookingSearchForm) == "function") { /* (doesn't exist on listingNotLive pages) */
    var result = initializeBookingSearchForm(listingOptions.locationName);
    var cookieWasAlreadySet = result.cookieWasAlreadySet;
    searchCriteria = result.searchCriteria;

    $(document).on('hostelz:doSearch', function (e, data) {
      bookingSearchGetFormValues(searchCriteria);
      bookingSearchSetCookie(searchCriteria);

      $(document).trigger('hostelz:topSearchBeforeSearch');

      if ( !data.locationField || data.locationField.itemId === 0 || data.locationField.itemId === listingID) {
        bookingUpdate();
      } else {
        window.location.href = data.locationField.searchURL !== ''
          ? data.locationField.searchURL
          : '/search?' + $.param( { search: data.locationField.query } );
      }
    });

    // $('.bookingSearchForm .bookingSubmitButton').click(function(event) {
    //   bookingUpdate();
    // });
    $('.bookingSearchForm .bookingSubmitButton').removeClass('disabled'); /* remove the 'disabled' class now that the form is ready to be submitted */

    /*  (if we want it to automatically update the availability info if the booking form changes)
        $('div.availForm select').change(function() {
            // req indicates that we're already showing results, so do an instant update... (listing page only, not for city page)
            // disabled this instant update... if (req && thisIsAListingPage) bookingUpdate(false);
            updateDatePicker();
        });
    */

    $(document).ready (function () {
      bookingUpdate();

      /* Map */
      $.Topic('mapSectionOpenClose').subscribe(mapOpenedOrClosed);

      $(document).on('hostelz:accordionOpened', function() {
        if ($('#mapContentBox').css('display') != 'none') mapOpenedOrClosed('opened');
      });

      $('.btn-load-map').click(function() {
        mapOpenedOrClosed('opened');

        $('body').on('hostelz:mapLoaded', function(){
          $('.map-overlay').fadeOut();
          $('.map-wrapper #mapCanvas').fadeIn();
        });
      });

      /* Submit Rating Stars */

      $('.stars i').click(function () {
        // the index is backwards because the stars are displayed backwards to make the hover effect work
        var rating = 5 - $(this).index();
        $('.stars input').val(rating);
        $('.stars i').each(function(index) {
          var starPosition = 5-index;
          if (starPosition <= rating)
            $(this).addClass('starSelected');
          else
            $(this).removeClass('starSelected');
        });
      });

      $('.own-rate label').click(function () {
        // the index is backwards because the stars are displayed backwards to make the hover effect work
        var rating = 5 - $(this).index();
        $('.stars input').val(rating);
      });

      /* Photo Lightbox */

      prepLightbox();

      loadMoreHostelsSection();
    });

    /* Panorama */
    $.Topic('panoramasSectionOpenClose').subscribe(panoramasSectionOpenedOrClosed);

    $(window).on('load', function () {
      /* Panorama */
      panoramasSectionOpenedOrClosed();
    });
  }

  // Save the last viewed city / listing so it can be used for the contact form, etc.

  document.cookie = "lastViewed=" + encodeURIComponent(document.location) + ";domain=." + listingOptions.domainName + ";path=/";

  initBSSelects();

  /* Show link for staff to edit the cityInfo */

  if (typeof loginInfoCookie.permissions != 'undefined' && loginInfoCookie.permissions.indexOf('staffCityInfo') != -1) {
    $('#editListing').addClass('d-flex');
  }
}

function loadMoreHostelsSection() {
  $.ajax({
    url: listingOptions.moreHostelsURL,
    success: function (htmlContent) {
      $('#listingMoreHostels').replaceWith(htmlContent);

      $(document).trigger('hostelz:exploreSectionLoaded');
    },
    error: function (xhr, textStatus, errorThrown) {
      console.warn(textStatus);
    }
  });
}

function bookingUpdate() {
  bookingShowWait('#bookingSearchResult', false);

  /* setTimeout() is used so there's a bit of a delay so they can see that the Check Availability button is doing something. todo: only delay if needed */
  // setTimeout(function () {
  $.ajax({
    url: listingOptions.listingBookingSearchURL + '/' + listingID + "?" + $.param(searchCriteria),
    success : function(htmlContent) {
      $('#bookingSearchResult').html(htmlContent);

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
    error : function(xhr, textStatus, errorThrown ) { bookingGeneralErrorMessage('#bookingSearchResult'); }
  });

  // url = '/bookingFetch.php?hostelID='+hostelID+'&'+'monthYear='+searchCriteria['monthYear']+'&day='+searchCriteria['day']+'&nights='+searchCriteria['nights']+'&people='+searchCriteria['people']+'&currency='+searchCriteria['currency']+'&city='+encodeURIComponent(city)+'&cityURL='+encodeURIComponent(cityURL);
  // }, 3000);
}

function panoramasSectionOpenedOrClosed()
{
  if (!$('.listingPanorama').length // no panoramas on the page
    || $('#panoramasContentBox').css('display') == 'none') // the section isn't opened
    return;

  $('.listingPanorama').each(function () {
    pannellum.viewer(this, {
      "type": "equirectangular",
      "panorama": $(this).attr('data-url'),
      "autoRotate": -0.9,
      "hfov": 110,
      "autoLoad": true,
      "showFullscreenCtrl": true,
      "compass": false
    });
  });
}

function prepLightbox()
{
  $('.picRow a, .fancyboxItem a')
    .click(function (event) {
      // Disable lightbox for small devices (the pics are already full screen size)
      if ($(window).width() < 700) { // this size should match the media query in the header of _listingDisplay.blade.php
        event.stopPropagation();
        event.preventDefault();
      }
    })
    .each(function () {
      /*we put the caption in the img alt tag (for SEO reasons), but fancybox wants it as the title of the "a" tag.*/
      $(this).attr('title', $('img',this).attr('alt'));
    })
  /* .fancybox({
              arrows: true,
              nextClick: true,
              openSpeed: 100,
              closeSpeed: 100,
              nextSpeed: 100,
              prevSpeed: 100,
              helpers: {
                  overlay: {
                      locked: false // keeps the page from jumping to the top when a photo is clicked
                  }
              },
        });*/

  var pics = [ ];

  $('[data-pic-group]').each(function () {
    if ($(this).parent().hasClass('swiper-slide-duplicate')) {
      return true;
    }

    var picGroup = $(this).attr('data-pic-group');
    if (!(picGroup in pics)) pics[picGroup] = [ ];

    var url = $(this).attr('data-fullsize-pic');
    if (typeof url == 'undefined') url = $(this).attr('src');

    pics[picGroup].push({
      href: url,
      title: $(this).attr('alt')
    });
  }).click(function (event) {
    showLightbox(pics, $(this));
  });

  // Allow clicking on the additional pics overlay to also trigger a click to show the lightbox
  $('.additionalPicsOverlay').click(function (event) {
    event.stopPropagation();
    showLightbox(pics, $(this).siblings('[data-pic-group]').first());
  });
}

function showLightbox(pics, $startingWithPic)
{
  var picIndex;
  var picGroup = $startingWithPic.attr('data-pic-group');

  const uniquePics = [...new Map(pics[picGroup].map((item) => [item.href, item])).values()];

  var source = $startingWithPic.attr('data-fullsize-pic');
  if (typeof source == 'undefined') source = $startingWithPic.attr('src');

  $.grep(uniquePics, function(pic, index) {
    if (pic.href == source && picIndex == undefined) {
      picIndex = index;
      return true;
    }
  });

  $.fancybox.open(uniquePics, {
    arrows: true, nextClick: true,
    openSpeed: 100, closeSpeed: 100, nextSpeed: 100, prevSpeed: 100,
    helpers: {
      overlay: {
        locked: false // keeps the page from jumping to the top when a photo is clicked
      }
    },
    index: picIndex
  });
}

function showPreviousAndNext()
{
  if (!$("#previousAndNextListings").length) return false; /* not display previous/next on this page */

  var listingsList = JSON.parse(localStorage.getItem('listingsList'));
  if (!listingsList) return false;

  var position = $.inArray(window.location.href, listingsList.listingURLs);
  if (position == -1) return false; /* this listing wasn't in the list */

  $('#listingsPosition').text(position+1);
  $('#listingsCount').text(listingsList.listingURLs.length);
  $('#cityURL').prop('href', listingsList.cityURL);

  if (position > 0) {
    $('#previousListing a').attr('href', listingsList.listingURLs[position-1]);
  } else {
    $('#previousListing').addClass('disabled');
  }
  if (position < listingsList.listingURLs.length-1) {
    $('#nextListing a').attr('href', listingsList.listingURLs[position+1]);
    $("<link />", { rel: "prefetch prerender", href: listingsList.listingURLs[position+1] }).appendTo("head");
  } else {
    $('#nextListing').addClass('disabled');
  }
  $('#previousAndNextListings').css('display', 'inline-block'); /* to make it visible */

  return true;
}


/* Stuff that is only done for the live listing page */

function getDynamicDataForListing(listingID, lastUpdatedForCurrentlyDisplayedListing)
{
  /* there's nothing we really need to do if a user isn't logged in. we could still check the timestamp, but probably not worth it just for that. */
  if (!loginInfoCookie) return;

  var alreadyDidReload = false;
  if (document.location.hash == "#reloaded") {
    alreadyDidReload = true;
    document.location.hash = ''; /* kind of works, but still leaves a '#" at the end of the url */
    history.replaceState({}, document.title, window.location.pathname + window.location.search); /* this also gets rid of the "#" but may not work on all browsers */
  }

  $.ajax({
    url: listingOptions.listingDynamicDataURL + '/'+listingID,
    xhrFields: { withCredentials: true } /* to include cookies */
  }).done(function (data) {
    /* Time Stamp */
    /* The listing was updated since this browser-cached version of the page was loaded, so force reload... */
    if (!alreadyDidReload && data.lastUpdateStamp > lastUpdatedForCurrentlyDisplayedListing) {
      document.location.hash = "#reloaded"; /* used so we know if the page was already reloaded to avoid any risk of an infinite loop */
      window.location.reload(true);
      return;
    }

    /* Edit Link */

    if (data.editListingLink) {
      $('#editListing').css('display', 'inline-block').attr('href', data.editListingLink);
    }
  });
}

/* Map */

var mapScriptDownloadStarted = false;

function mapOpenedOrClosed(status)
{
  if (status == 'closed' || // nothing to do, we only care if it was opened
    typeof doAfterMapScriptIsLoaded != 'function') // or if there's no map on this listing page
    return;

  if (!mapScriptDownloadStarted) {
    mapScriptDownloadStarted = true;
    $.getScript("//maps.googleapis.com/maps/api/js?v=3&key=" + listingOptions.googleApiKey + "&callback=doAfterMapScriptIsLoaded&language=" + listingOptions.language + "")
  }
}

function displayMap(latitude, longitude)
{
  var mapCanvas = document.getElementById("mapCanvas");
  if (!mapCanvas) {
    return;
  }

  var listingPoint = new google.maps.LatLng(latitude, longitude);
  var map = new google.maps.Map(document.getElementById("mapCanvas"),
    {
      zoom: 12,
      minZoom: 4,
      center: listingPoint,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      scaleControl: false,
      zoomControl: false,
      mapTypeControl: false,
      streetViewControl: false,
      rotateControl: false,
      fullscreenControl: false,
    });

  var marker = new google.maps.Marker({
    position: listingPoint,
    /* title: pointName, */
    map: map,
    icon: {
      url: listingOptions.mapMarker.mapMarkerCities,
      size: new google.maps.Size(listingOptions.mapMarker.width, listingOptions.mapMarker.height),
      scaledSize: new google.maps.Size(
        listingOptions.mapMarker.width,
        listingOptions.mapMarker.height
      ),
      origin: new google.maps.Point(0, 0),
      anchor: new google.maps.Point(listingOptions.mapMarker.width, listingOptions.mapMarker.height)
    },
  });

  map.addListener('click', function() {
    window.open('https://maps.google.com/?q=' + latitude + ',' + longitude, '_blank');
  });

  /* Street View */

  // First check to see if a street view panorama exists at this location
  var streetViewService = new google.maps.StreetViewService();
  streetViewService.getPanorama({location: listingPoint, radius: 50, source: google.maps.StreetViewSource.OUTDOOR},
    function (data, status) {
      if (status !== google.maps.StreetViewStatus.OK) return;
      // Note: we pass the panorama's lat/long rather than using listingPoint because it will use our preferred OUTDOOR source, etc.
      addStreetView(map, data.location.latLng);
      google.maps.event.trigger(map, "resize");
      map.setCenter(listingPoint);
    }
  );

  $('body').trigger('hostelz:mapLoaded');
}

function addStreetView(map, point)
{
  $('.mapCanvasDiv').removeClass('col-md-12').addClass('col-md-6');
  $('.streetViewDiv').css('display', 'block'); // display it
  map.setStreetView(new google.maps.StreetViewPanorama(
    document.getElementById('streetView'),
    { addressControl: true, position: point }
  ));
}
