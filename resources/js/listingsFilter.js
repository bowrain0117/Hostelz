function initFilterClick() {
    const filterCheckbox = $('.filter-option');
    const spinner = $('#searchFiltersSubmit .spinner-wrap');
    const submitButton = $('#searchFiltersSubmit span');

    filterCheckbox.click(function (e) {
        submitButton.text('show results')
        spinner.removeClass('d-none')

        pushSelectedValuesFromFilters($(document.getElementById($(this).attr('for'))), cityPageSettings.listingFilters)
        pushCollapseShowValues('#filter-collapse-' + $(document.getElementById($(this).attr('for'))).attr('name'), collapseShow)
        updateFiltersCount()
    })

    spinner.addClass('d-none')
}

function initFilters() {
    let modal = $('#searchFilters');
    let submitButton = $('#searchFiltersSubmit');
    let clearButton = $('#searchFiltersClear');
    const spinner = $('#searchFiltersSubmit .spinner-wrap');

    submitButton.click(function (e) {
        bookingShowWait('#listingsSearchResult', true);
        updateListingsListUsingFiltersAndSearchCriteria(true, true);

        modal.modal('hide');
    });

    //  clear filters options
    clearButton.click(function () {
        submitButton.find('span').text('show results')
        spinner.removeClass('d-none')
        cityPageSettings.listingFilters = {}
        updateCollapseShowValues(true)
        updateFiltersCount()
    });
}

function addListenerForDistrictFilter() {
    // set orderBy from filter if has one
    $(document).on("hostelz:beforeSetCookiesForListingSearch", function (e, searchOptions) {
        if (!searchOptions.listingFilters || !searchOptions.listingFilters.hasOwnProperty('district')) {
            return searchOptions;
        }

        const districtOrderBy = cityOptions.searchData.districts.find(element => element.value == searchOptions.listingFilters.district[0]);
        if (districtOrderBy) {
            searchOptions.resultsOptions.orderBy = districtOrderBy;
        }

        return searchOptions;
    })
}

function addListenerForDistrictSort() {
    // set orderBy to default in district not exist in current city
    $(document).on("hostelz:beforeSetCookiesForListingSearch", function (e, searchOptions) {
        if (searchOptions.resultsOptions.orderBy.type !== 'district') {
            return searchOptions;
        }

        const existsDistrictInCity = cityOptions.searchData.districts.find(element => element.value == searchOptions.resultsOptions.orderBy.value);
        if (!existsDistrictInCity) {
            searchOptions.resultsOptions.orderBy = globalOptions.defaultListingsShowOptions.resultsOptions.orderBy;
        }

        return searchOptions;
    })
}

function getSelectedValuesFromFilters(parentSelector) {
    var values = {};
    $(parentSelector).find('input:checked').each(function () {
        pushSelectedValuesFromFilters($(this), values)
    });

    return values;
}

function pushSelectedValuesFromFilters(item, values) {
    const menuName = item.attr('name');
    let selectedValues = item.val();

    if (!selectedValues) return;

    if (!values[menuName]) {
        values[menuName] = [];
    }

    if ((menuName === 'poi' || menuName === 'typeOfDormRoom') && values[menuName]) {
        values[menuName] = [];
    }

    if (menuName === 'neighborhoods') {
        selectedValues = selectedValues.replace(/ /g, '_')
    }

    if (values[menuName].includes(selectedValues)) {
        values[menuName] = values[menuName].filter(item => item !== selectedValues)

        if (values[menuName].length === 0) {
            delete values[menuName]
        }
        return;
    }

    values[menuName].push(selectedValues);
}

function updateCheckedFilters(filters) {
    if (!filters) {
        return;
    }

    Object.keys(filters).forEach((filter) => {
        filters[filter].forEach((val) => {
            let id;
            try {
                let options = JSON.parse(val);
                id = filter + '-' + options.sortBy + options.value;
            } catch (e) {
                id = filter + '-' + val;
            }

            const element = document.getElementById(id);
            if (element) {
                document.getElementById(id).checked = true
            }
        })
    })
}

function addCountToFilterTitle() {
    if ($.isEmptyObject(cityPageSettings.listingFilters)) return;

    for (const filter in cityPageSettings.listingFilters) {
        let filterTitle = $('#filter-title-' + filter)
        let filterCollapse = $('#filter-collapse-' + filter)

        if (filterTitle.hasClass('filter-title-active')) {
            filterTitle.removeClass('filter-title-active')
            filterCollapse.removeClass('show')
        }

        filterTitle.addClass('filter-title-active').attr('data-cont', cityPageSettings.listingFilters[filter].length)
        filterCollapse.addClass('show')
    }
}

function updateFiltersCount(page, bookingSearchButtonClicked) {
    if (typeof page == 'undefined') page = 1;

    const spinner = $('#searchFiltersSubmit .spinner-wrap')

    let options = cityPageSettings
    options['bookingSearchCriteria'] = bookingSearchCriteria
    options['page'] = page

    $.ajax({
        url: '/hostels-city-filters/' + cityID,
        data: {
            'options': options
        },
        success(data) {
            $('.listingFilters').html($(data).html())

            updateAvailableListingsCount(data)

            if (!spinner.hasClass('d-none')) {
                spinner.addClass('d-none')
            }

            addCountToFilterTitle()
            updateCheckedFilters(cityPageSettings.listingFilters)
            updateCollapseShowValues(bookingSearchButtonClicked)

            initFilterClick()
        },
        error: function (xhr, textStatus) {
            console.log(textStatus)
        }
    })
}

function addFilterButtonHighlight() {
    if ($.isEmptyObject(cityPageSettings.listingFilters)) return;

    let filtersCount = 0

    for (const filter in cityPageSettings.listingFilters) {
        if (!Array.isArray(cityPageSettings.listingFilters[filter])) {
            filtersCount++
            continue
        }

        filtersCount = filtersCount + cityPageSettings.listingFilters[filter].length
    }

    $('#city-filter').addClass('city-filter-active').attr('data-content', filtersCount)
}

/*city-sortBy*/
function initSortBy() {
    var wrap = $('#city-sortBy');
    var button = wrap.find('button');
    var items = wrap.find('.city-sortBy-item');
    var currentValue = cityPageSettings.resultsOptions['orderBy'];

    items.click(function () {
        var newValue = $(this).data('value');

        if (newValue === currentValue) {
            return true;
        }

        currentValue = newValue;

        items.removeClass('selected');
        $(this).addClass('selected');

        updateTitle($(this).text().trim());

        cityPageSettings.resultsOptions['orderBy'] = newValue;

        bookingShowWait('#listingsSearchResult', true);
        updateListingsListUsingFiltersAndSearchCriteria(true, true);

        // $(document).trigger('hostelz:citySortByChanged', value);
    });


    function updateTitle(value) {
        button.find('.city-sort-value').text(value);
    }
}