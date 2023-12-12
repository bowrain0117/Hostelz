/*get user data by cookies*/
var loginInfoCookie = getMultiCookie('loginInfo', false);
var frontUserData = {};

/*Login Display*/
menuLoginInit();

function menuLoginInit() {
    loginInfoCookie = getMultiCookie('loginInfo', false);
    if (loginInfoCookie) {
        $("#loggedInUser").html(loginInfoCookie['username']);
        $("#loggedInUserPoints").html(loginInfoCookie['points']);
        $("#loggedIn").css('display', 'flex');
        $("#loggedOut").css('display', 'none');
    } else {
        $("#loggedOut").css('display', 'flex');
        $("#loggedIn").css('display', 'none');
    }
}

function loadFrontUserData() {
    frontUserData.show = 1;
    $.ajax({
        url: '/user/frontUserData',
        data: $(document).triggerHandler("hostelz:frontUserData", frontUserData),
        success: function (data) {
            $(document).trigger('hostelz:loadedFrontUserData', [data]);
        },
        error: function (xhr, textStatus, errorThrown) {
            console.log('frontUserData error');
        }
    });
}

function loadFavicon() {
    const favicon = document.querySelector('#favicon')

    if (!favicon) {
        return;
    }

    const title = document.querySelector('title')
    const titleText = title.innerHTML

    window.onfocus = () => {
        title.innerHTML = titleText
        favicon.href = '/favicon.ico'
    }
    window.onblur = () => {
        title.innerHTML = 'Still want to travel? ' + titleText
        favicon.href = '/favicon-yellow.ico'
    }
}

$(document).ready(function () {
    loadFavicon();
    loadFrontUserData();
    $('body').on('hostelz:wishlistLoginSuccess', function () {
        menuLoginInit();
        loadFrontUserData();
    })
});

/* To allow publishing/subscribing events.  See https://api.jquery.com/jQuery.Callbacks/ */

var topics = {};
jQuery.Topic = function (id) {
    var callbacks, method, topic = id && topics[id];

    if (!topic) {
        callbacks = jQuery.Callbacks();
        topic = {
            publish: callbacks.fire,
            subscribe: callbacks.add,
            unsubscribe: callbacks.remove
        };
        if (id) {
            topics[id] = topic;
        }
    }
    return topic;
};

$(document).ready(function () {
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    if (typeof articleOptions === 'undefined') {
        return;
    }

    /* Make the title click-able for staff to edit the article */
    if (typeof loginInfoCookie.permissions != 'undefined' && loginInfoCookie.permissions.indexOf('admin') != -1) {
        $('.article-author-info').prepend("<div class='row mb-3'><div class='col'><a href='" + articleOptions.edit.link + "'>" + articleOptions.edit.text + "</a></div></div>");
    }
});

var domainCookie = globalOptions.config.domainName !== ''
    ? 'domain=.' + globalOptions.config.domainName + ';'
    : '';

$(document).ready(function () {

    /*Search Autocomplete*/

    $('.websiteSearch').devbridgeAutocomplete({
        serviceUrl: globalOptions.routes.searchAutocomplete,
        paramName: 's',
        minChars: 2,
        groupBy: 'category',
        preserveInput: true,
        triggerSelectOnValidInput: false,
        deferRequestBy: 100, /*wait briefly to see if they hit another character before querying*/
        onSelect: function (suggestion) {
            $(this).val(suggestion.data.query);
            $(this).closest('form').submit();
        },
    });

    /*Show/hide content boxes*/

    $('.contentBox .contentBoxTitle a').click(function (event) {
        event.preventDefault();
        var $contentBox = $(this).parent().siblings('.contentBoxContent');
        // so data-event-topic can be set for the section if we want to subscribe to open/close events
        var eventTopic = $(this).data('eventTopic');
        if ($contentBox.css('display') == 'none') {
            $contentBox.css('display', 'block');
            $(this).find('.fa-caret-right').css('display', 'none');
            $(this).find('.fa-caret-down').css('display', 'inline-block');
            if (eventTopic) $.Topic(eventTopic).publish('opened'); // broadcast event to any interested listeners
        } else {
            $contentBox.css('display', 'none');
            $(this).find('.fa-caret-right').css('display', 'inline-block');
            $(this).find('.fa-caret-down').css('display', 'none');
            if (eventTopic) $.Topic(eventTopic).publish('closed'); // broadcast event to any interested listeners
        }
    });

    /*Plus/Minus Buttons*/

    $('.plusMinus button').click(function (e) {
        e.preventDefault();
        var $input = $(this).parent().find('input');
        var value = parseInt($input.val());
        if (isNaN(value)) value = 0;
        if ($(this).text() == '-') {
            if (value > $input.attr('min')) {
                $input.val(value - 1);
                $input.change(); // trigger event handlers
            }
        } else {
            if (value < $input.attr('max')) {
                $input.val(value + 1);
                $input.change(); // trigger event handlers
            }
        }
    });

    /*Save the original referrer to "origination" cookie so we can track where people came from.*/

    if (document.referrer != '' && document.cookie.indexOf(globalOptions.config.originationReferrerCookie) < 0) {
        document.cookie = globalOptions.config.originationReferrerCookie + '=' + encodeURIComponent(document.referrer) + ';' + domainCookie + 'path=/';
    }

    /*looks for "usrc" get variable (affiliate userID referral code)*/

    if (window.location.href.indexOf('usrc=') > -1) {
        var usrc = getQueryVariable('usrc');
        if (usrc != null) {
            document.cookie = globalOptions.config.affiliateIdCookie + '=' + encodeURIComponent(usrc) + ';' + domainCookie + 'path=/';
        }
    }

    /*for radio buttons*/

    $('.custom-control-label').click(function () {
        var item = $(this).prev();
        if (item.attr('type') === 'radio') {
            $(this).prev().prop('checked', 'checked').change();
        }
    });
});

function generalLoadFailedErrorMessage(selector) {
    $(selector).html(globalOptions.sections.generalLoadFailedErrorMessage);
}

function getQueryVariable(param) {
    var vars = {};
    window.location.href.replace(location.hash, '').replace(
        /[?&]+([^=&]+)=?([^&]*)?/gi,
        function (m, key, value) {
            vars[key] = value !== undefined ? value : '';
        }
    );

    if (param) return vars[param] ? vars[param] : null;
    return vars;
}

/*Multi-Cookie*/

function getMultiCookie(name, isSecure) {
    var c = document.cookie.match('(^|;)?' + name + '=([^;]*)(;|$)');
    if (c == null) return false;
    c = decodeURIComponent(c[2]);
    if (isSecure) c = c.substr(c.indexOf(":") + 1); /* ignored, we don't actually check the security in this JS function */

    try {
        return JSON.parse(c);
    } catch (e) {
        return [];
    }
}

/* expireMinutes parameter is optional (makes a session cookie by default). Pass expireMinutes as -1 to delete a cookie.  */

function setMultiCookie(name, values, expireMinutes) {
    var expirationString = '';

    if (typeof expireMinutes != 'undefined') {
        var d = new Date();
        d.setTime(d.getTime() + (expireMinutes * 60 * 1000));
        expirationString = "expires=" + d.toGMTString() + ';';
    }

    document.cookie = name + '=' + encodeURIComponent(JSON.stringify(values)) + ';' + domainCookie + expirationString + 'path=/';
}


/* Misc */

function escapeRegExp(str) {
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

function showWordCount(fieldName) {
    var textField = $('[name="' + fieldName + '"]');
    $('<div class="wordCount"><span></span> words</div>').insertAfter(textField);
    var updateCount = function () {
        var count;
        if ($(this).val() == '')
            count = 0;
        else
            count = $(this).val().replace(/\s+/gi, ' ').split(' ').length;
        $(this).next('div.wordCount').children('span').text(count);
    };
    textField.each(updateCount);
    textField.on('input', updateCount);
}

function setFormValues($form, formVariableName, values) {
    $.each(values, function (key, value) {
        if ($.isArray(value)) {
            var values = value;
            var $elements = $('[name=' + formVariableName + '\\[' + key + '\\]\\[\\]]', $form);
            if (!$elements.length) return;
            $elements.each(function () {
                switch ($(this).attr("type")) {
                    case 'checkbox':
                        $(this).prop('checked', $.inArray($(this).val(), values) != -1);
                        break;
                }
            });
        } else {
            var $element = $('[name=' + formVariableName + '\\[' + key + '\\]]', $form);
            if (!$element.length) return;
            switch ($element.attr("type")) {
                case 'checkbox':
                    $element.prop('checked', value ? true : false);
                    break;
                case 'radio':
                    /* We use click() because it triggers events and it works with Bootstrap Javascript radio buttons. */
                    $element.filter('[value="' + value + '"]').click().change();
                    break;
                default:
                    $element.val(value);
            }
        }
    });

    $(document).trigger('hostelz:setFormValues');
}

/* The values variable has to already have been initialized to an object with the fields for the values we want already defined. */

function getFormValues($form, formVariableName, values) {
    $.each(values, function (key, value) {
        if ($.isArray(value)) {
            var $elements = $('[name=' + formVariableName + '\\[' + key + '\\]\\[\\]]', $form);
            values[key] = [];
            if (!$elements.length) return;
            $elements.each(function () {
                switch ($(this).attr("type")) {
                    case 'checkbox':
                        if ($(this).is(':checked')) values[key].push($(this).val());
                        break;
                }
            });
        } else {
            var $element = $('[name=' + formVariableName + '\\[' + key + '\\]]', $form);
            if (!$element.length) return;
            switch ($element.attr("type")) {
                case 'checkbox':
                    values[key] = $element.is(':checked');
                    break;
                case 'radio':
                    /* We use click() because it triggers events and it works with Bootstrap Javascript radio buttons. */
                    values[key] = $element.filter(':checked').val();
                    break;
                default:
                    values[key] = $element.val();
            }
        }
    });
}

function setHtmlToBigWaitSpinner(selector) {
    $(selector).html('<div class="bigWaitingSpinner"><i class="fa fa-cog fa-spin"></i></div>');
}

function triggerHandler(eventName, data) {
    const event = new CustomEvent(eventName, {detail: data});

    document.dispatchEvent(event);

    return event.detail
}


/* Math */

function roundToNearest(number, roundTo) {
    // (rounds up if the result would have been 0)
    var result = Math.round(number / roundTo);
    return Math.max(result, 1) * roundTo;
}

function nearestCeil(number, nearest) {
    return Math.ceil(number / nearest) * nearest;
}

function nearestFloor(number, nearest) {
    return Math.floor(number / nearest) * nearest;
}


/* Selectable Dropdown Menus */

function initializeDropdownMenus(parentSelector, menuItemValues, itemSelectedCallback, itemDeselectedCallback) {
    $(parentSelector).find('ul.dropdown-menu li.menuOptionSelected').removeClass('menuOptionSelected'); // reset all

    if (menuItemValues) {
        $.each(menuItemValues, function (menuName, values) {
            if (!values.length) return;
            if (Array.isArray(values)) {
                $.each(values, function (itemIndex, menuItemValue) {
                    toggleDropdownMenuItem(parentSelector, menuName, menuItemValue);
                });
            } else {
                toggleDropdownMenuItem(parentSelector, menuName, values);
            }
        });
    }

    $(parentSelector).find('ul.dropdown-menu a').click(function (e) {
        e.preventDefault();
        var menuName = $(this).closest('[data-dropdown-name]').attr('data-dropdown-name');
        var menuItemValue = $(this).closest('[data-dropdown-value]').attr('data-dropdown-value');
        toggleDropdownMenuItem(parentSelector, menuName, menuItemValue, itemSelectedCallback, itemDeselectedCallback);
    });
}

function getSelectedValuesFromDropdownMenus(parentSelector, ignoreIfNoItemsSelected) {
    var values = {};

    $(parentSelector).find('[data-dropdown-name]').each(function () {
        var menuName = $(this).attr('data-dropdown-name');
        var selectType = $(this).attr('data-dropdown-select-type');
        var selectedValues = getSelectedValuesFromDropdownMenu($(this), selectType);
        if (!ignoreIfNoItemsSelected || selectedValues.length > 0) values[menuName] = selectedValues;
    });

    return values;
}

function getSelectedValuesFromDropdownMenu($menuObject, selectType) {
    var values = [];

    $menuObject.find('.menuOptionSelected[data-dropdown-value]').each(function () {
        var menuItemValue = $(this).attr('data-dropdown-value');
        if (selectType == 'multiple') {
            values.push(menuItemValue);
        } else {
            values = menuItemValue;
        }
    });

    return values;
}

function dropdownMenuDeselectAll($menuObject) {
    var selectType = $(this).attr('data-dropdown-select-type');
    // We use click() so the event handlers and callbacks are called.
    var values = getSelectedValuesFromDropdownMenu($menuObject, selectType);

    if (selectType == 'multiple') {
        $.each(values, function (index, value) {
            getDropdownMenuItemByValue($menuObject, value).find('a').click();
        });
    } else {
        getDropdownMenuItemByValue($menuObject, values).find('a').click();
    }
}

function toggleDropdownMenuItem(parentSelector, menuName, menuItemValue, itemSelectedCallback, itemDeselectedCallback) {
    var select = $(parentSelector).find("[name='" + menuName + "']");
    if (!select.length) {
        return;
    }

    select.val(menuItemValue);
}

/* We have to do this instead of using a simple jQuery selector because it's too tricky to escape the menuItemValue string to use it in a jQuery selector. */

function getDropdownMenuItemByValue($parent, menuItemValue) {
    return $parent.find("[data-dropdown-value]").filter(function () {
        return $(this).attr('data-dropdown-value') == menuItemValue;
    });
}


/* Mapping */

function createMap(mapDivId, mapBounds) {
    var mapCenter = [(parseFloat(mapBounds.swPoint.latitude) + parseFloat(mapBounds.nePoint.latitude)) / 2, (parseFloat(mapBounds.swPoint.longitude) + parseFloat(mapBounds.nePoint.longitude)) / 2];

    var map = new google.maps.Map(document.getElementById(mapDivId), {
        zoom: 10,
        minZoom: 2,
        center: new google.maps.LatLng(mapCenter[0], mapCenter[1]),
        panControl: false,
        zoomControl: true,
        mapTypeControl: false,
        scaleControl: true,
        streetViewControl: false,
        fullscreenControl: false,
        overviewMapControl: false,
        styles: []
    });

    map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(mapBounds.swPoint.latitude, mapBounds.swPoint.longitude),
        new google.maps.LatLng(mapBounds.nePoint.latitude, mapBounds.nePoint.longitude)));

    return map;
}

function extendMapBoundsToLatitudeLongitude(map, latitude, longitude) {
    map.fitBounds(map.getBounds().extend(new google.maps.LatLng(latitude, longitude)));
}

/*
    zoomDivisor - Smaller numbers make the marker bigger. 19 is 1:1 ratio with the zoom.
    roundToNearestValue - can be used for better scaling with less jaggies by using a common factor of the icon width/height.
*/

function scaleIconToZoomLevel(map, icon, zoomDivisor, roundToNearestValue, anchorWidthPositionMultiple, anchorHeightPositionMultiple) {
    // (we round to the nearest 4 for better scaling with less jaggies)
    var markerScaledWidth = Math.min(roundToNearest(icon.size.width * map.getZoom() / zoomDivisor, roundToNearestValue), icon.size.width);
    var markerScaledHeight = Math.min(roundToNearest(icon.size.height * map.getZoom() / zoomDivisor, roundToNearestValue), icon.size.height);

    /*    icon.scaledSize = new google.maps.Size(markerScaledWidth, markerScaledHeight);*/

    icon.scaledSize = new google.maps.Size(
        icon.size.width,
        icon.size.height
    );

    icon.anchor = new google.maps.Point(
        Math.round(markerScaledWidth * anchorWidthPositionMultiple),
        Math.round(markerScaledHeight * anchorHeightPositionMultiple)
    );
}

var dynamiclyLoadededCityMarkers = [];
var dynamiclyLoadededCityMarkerIcon, dynamiclyLoadededCityMarkerIconHighlighted;

function dynamiclyLoadededCityMarkersInitialize(useMutedIcon) {
    dynamiclyLoadededCityMarkerIcon = {
        url: useMutedIcon ? globalOptions.routes.mapMarkerCityMuted : globalOptions.routes.mapMarkerCity,
        size: new google.maps.Size(globalOptions.cityMapMarkerWidth, globalOptions.cityMapMarkerHeight),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(globalOptions.cityMapMarkerWidth, globalOptions.cityMapMarkerHeight)
    };

    dynamiclyLoadededCityMarkerIconHighlighted = $.extend({}, dynamiclyLoadededCityMarkerIcon, {url: globalOptions.routes.mapMarkerCityHighlighted});
}

/*
    zoomDivisor - Smaller numbers make the marker bigger. 19 is 1:1 ratio with the zoom.
*/

function dynamiclyLoadededCityMarkersRefresh(map, exceptParameter, zoomDivisor) {
    var bounds = map.getBounds(), neLatLng = bounds.getNorthEast(), swLatLng = bounds.getSouthWest();

    var roundToNearest = (Math.abs(neLatLng.lng() - swLatLng.lng()) > 10 ? 10 : 5);

    // Rounded for better caching
    if (swLatLng.lng() > neLatLng.lng()) { // (crosses the prime meridian, so SW is the min longitude, and NE is the max)
        var neLon = nearestFloor(neLatLng.lng(), roundToNearest);
        var swLon = nearestCeil(swLatLng.lng(), roundToNearest);
    } else {
        var neLon = nearestCeil(neLatLng.lng(), roundToNearest);
        var swLon = nearestFloor(swLatLng.lng(), roundToNearest);
    }

    var swLat = nearestFloor(swLatLng.lat(), roundToNearest);
    var neLat = nearestCeil(neLatLng.lat(), roundToNearest);

    $.get(globalOptions.routes.cityMarkerPoints + "?" + exceptParameter + "&box[swLatitude]=" + swLat + "&box[swLongitude]=" + swLon +
        "&box[neLatitude]=" + neLat + "&box[neLongitude]=" + neLon, function (data) {

        /* Remove old ones */
        $.each(dynamiclyLoadededCityMarkers, function (i, marker) {
            marker.setMap(null);
        });
        dynamiclyLoadededCityMarkers = [];

        scaleIconToZoomLevel(map, dynamiclyLoadededCityMarkerIcon, zoomDivisor, 4, 0.5, 1.0);
        scaleIconToZoomLevel(map, dynamiclyLoadededCityMarkerIconHighlighted, zoomDivisor, 4, 0.5, 1.0);

        $.each(data.points, function (i, point) {
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(point.latitude, point.longitude),
                map: map,
                icon: dynamiclyLoadededCityMarkerIcon,
                title: point.cityName
            });
            dynamiclyLoadededCityMarkers.push(marker);

            google.maps.event.addListener(marker, 'click', function () {
                window.location.href = point.url;
            });
            google.maps.event.addListener(marker, 'mouseover', function () {
                marker.setIcon(dynamiclyLoadededCityMarkerIconHighlighted);
            });
            google.maps.event.addListener(marker, 'mouseout', function () {
                marker.setIcon(dynamiclyLoadededCityMarkerIcon);
            });
        });
    });
}

/* City/Cities Page Stuff */

function getCityPageSettingsCookie(forCityID) {
    var cityPageSettings = getMultiCookie(globalOptions.config.searchCriteriaCookie, false);

    if (!cityPageSettings) {
        cityPageSettings = globalOptions.defaultListingsShowOptions;
    } else if (cityPageSettings.cityID != forCityID) {
        // Only keep a some settings from the other city
        cityPageSettings = {
            resultsOptions: cityPageSettings.resultsOptions,
            doBookingSearch: cityPageSettings.doBookingSearch,
            listingFilters: {}
        };
    }

    cityPageSettings.cityID = forCityID;

    return cityPageSettings;
}

function setCityPageSettingsCookie(cityPageSettings) {
    setMultiCookie(globalOptions.config.searchCriteriaCookie, cityPageSettings);
}

function turnDoBookingSearchForCityTo(cityID, status) {
    var cityPageSettings = getCityPageSettingsCookie(cityID);
    cityPageSettings.doBookingSearch = status === true;
    setCityPageSettingsCookie(cityPageSettings);
}

/*  initUserMenu  */

$(document).ready(function () {
    toggleOnScroll()
});

function toggleOnScroll() {
    document.querySelectorAll(".toogleOnScroll").forEach((element) => {
        document.addEventListener("hostelz:scrollBottom", () => element.classList.add("toBottom"))
        document.addEventListener("hostelz:scrollTop", () => element.classList.remove("toBottom"))
    });
}

$(document).ready(function () {
    initScrollDispatch();
});

function initScrollDispatch() {
    var didScroll;
    var lastScrollTop = 0;
    var delta = 5;

    var minHeight = 150;

    $(window).scroll(function () {
        didScroll = true;
    });

    setInterval(function () {
        if (didScroll) {
            hasScrolled();
            didScroll = false;
        }
    }, 250);

    function hasScrolled() {
        var st = $(this).scrollTop();

        if (Math.abs(lastScrollTop - st) <= delta) {
            return;
        }

        if (st > minHeight && st > lastScrollTop) {
            hideMenu();
        } else {
            if (st + $(window).height() < $(document).height()) {
                showMenu();
            }
        }

        lastScrollTop = st;
    }

    function hideMenu() {
        document.dispatchEvent(new Event("hostelz:scrollBottom"));
    }

    function showMenu() {
        document.dispatchEvent(new Event("hostelz:scrollTop"));
    }
}

$(document).ready(function () {
    $(document).on('hostelz:bookingUpdated', function () {
        initBSSelects();
    });
});


function initBSSelects() {
    $('select.selectpicker').selectpicker({
        dropdownAlignRight: 'auto'
    });

    // $('#listingFilterOrderBy').on('loaded.bs.select', function (e) {
    //     $("[data-id=\"listingFilterOrderBy\"]").find('.filter-option-inner-inner').html('<i class="fas fa-sort"></i>');
    // });
}

/* show user avatar */
$(document).ready(function () {
    $(document).on('hostelz:loadedFrontUserData', function (e, data) {
        localStorage.comparisonIds = data.comparisonIds

        if (document.querySelector('.comparison-sticky-mobile .comparison-count')) {
            document.querySelector('.comparison-sticky-mobile .comparison-count').innerHTML = data.comparisonListingsCount
        }
        if (document.querySelector('#loggedOut .comparison-count')) {
            document.querySelector('#loggedOut .comparison-count').innerHTML = data.comparisonListingsCount
        }

        if (!data.isLogged) {
            return true;
        }

        $("#headerUserSettings").replaceWith(data.headerSettings);
        $('#reviewUserAvatar').replaceWith(data.avatar);
        $('#createAccountText').replaceWith(data.userName);

        $('#userBottomMenuLogin').hide();
        $('#userBottomMenuProfile').show();

        if (document.querySelector('.currencySelectorMenuPlaceholder')) {
            insertCurrencySelector('.currencySelectorMenuPlaceholder', data.localCurrency, '');
            initBSSelects();
        }
    });

    showBlocksIfNotLogin();
    showBlocksIfLogin();
    removeBlocksIfNotLogin();


    function showBlocksIfNotLogin() {
        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (data.isLogged) {
                return true;
            }

            $('.js-show-if-not-login').show();
        });
    }

    function showBlocksIfLogin() {
        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (!data.isLogged) {
                return true;
            }

            $('.js-show-if-login').show();
        });
    }

    function removeBlocksIfNotLogin() {
        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (data.isLogged) {
                return true;
            }

            $('.js-remove-if-not-login').remove();
        });
    }
});

$(document).ready(function () {
    var body = $('body');

    body.on('click', '.js-open-search-location', function (e) {
        e.preventDefault();

        $("html").animate(
            {scrollTop: 0},
            {
                duration: 1000,
                easing: 'easeOutSine',
                complete: function () {
                    $('#header-search-result__location').click();
                },
            });
    })

    body.on('click', '.js-open-search-dates', function (e) {
        e.preventDefault();

        $("html").animate(
            {scrollTop: 0},
            {
                duration: 1000,
                easing: 'easeOutSine',
                complete: function () {
                    $('#header-search-result__dates').click();
                },
            });
    })
});

function userSearchHistory(suggestion) {
    $.ajax({
        url: globalOptions.routes.userSearch,
        method: 'post',
        data: {
            category: suggestion.data.category,
            query: suggestion.data.query,
            itemID: suggestion.data.itemId,
        },
        // success : function(data) {
        //     // console.log(data);
        // },
        // error : function(xhr, textStatus, errorThrown ) { console.log('userSearchHistory error'); }
    });
}

//

function slugify(text) {
    return text
        .toString()                                         // Cast to string (optional)
        .normalize('NFKD')                             // The normalize() using NFKD method returns the Unicode Normalization Form of a given string.
        .toLowerCase()                                      // Convert the string to lowercase letters
        .trim()                                             // Remove whitespace from both sides of a string (optional)
        .replace(/\s+/g, '-')         // Replace spaces with -
        .replace(/[^\w\-]+/g, '')     // Remove all non-word chars
        .replace(/\_/g, '-')          // Replace _ with -
        .replace(/\-\-+/g, '-')       // Replace multiple - with single -
        .replace(/\-$/g, '');         // Remove trailing -
}

function getFirstWordsFromString(string, number) {
    return string.split(/\s+/).slice(0, number).join(" ");
}