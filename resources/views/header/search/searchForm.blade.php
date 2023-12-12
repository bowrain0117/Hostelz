<div id="header-search-form__wrap" class="col" style="display: none;">
    <div class="d-flex justify-content-center">
        <div id="header-search-form" class="mb-lg-5 d-inline-block">
            <form action="{!! routeURL('search') !!}" id="" class="bookingSearchForm d-flex flex-row">

                <div id="header-search-form__location" class="delimiter-line search-dropdown-wrap">
                    <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Location</button>
                    <div class="dropdown-menu location-suggestion p-3 p-lg-5" aria-labelledby="dropdownMenuButton">
                        <div class="form-inline mb-3" style="padding-left: 1px;">
                            <span class="form-group position-relative">
                                <span class="position-absolute" style="left: 20px; fill: #4A5268;">@include('partials.svg-icon', ['svg_id' => 'search-icon', 'svg_w' => '22', 'svg_h' => '22'])</span>

                                <input name="location" placeholder="{{{ langGet('index.EnterAName') }}}" type="text" class="searchLocation form-control bg-light rounded-xl border-0 cl-subtext" style="padding-left: 54px; padding-right: 54px;" />

                                <span class="position-absolute cursor-pointer search-autocomplete-clear" style="right: 20px; fill: #4A5268;">@include('partials.svg-icon', ['svg_id' => 'close-icon', 'svg_w' => '22', 'svg_h' => '22'])</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-center spinner-wrap mt-3 d-none-i">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="header-search-form__dates" class="delimiter-line search-dropdown-wrap">
                    <button class="btn-clear date-title" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Add dates
                    </button>
                    <div class="dropdown-menu datepicker-search-top" aria-labelledby="dropdownMenuButton">
                        <input type="text" id="searchDateTop" class="d-none">
                        <input type="hidden" name="searchCriteria[startDate]" id="bookingSearchDate" >
                        <input type="hidden" name="searchCriteria[nights]" id="bookingSearchNights" >
                    </div>
                </div>

                <div id="header-search-form__roomType" class="delimiter-line search-dropdown-wrap bookingSearchRoomType">
                    <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Room type
                    </button>
                    <div class="dropdown-menu p-3 p-lg-5" aria-labelledby="dropdownMenuButton">
                        <label for="" class="pre-title mb-2">Room type</label>
                        <div class="flex-shrink-0 bk-search__item bookingSearchRoomType bs-switcher mb-2 mb-lg-0">
                            <span class="bs-switcher__item">
                                <input type="radio" id="radiodormHeader" value="dorm" name="searchCriteria[roomType]">
                                <label for="radiodormHeader">@langGet('bookingProcess.searchCriteria.dormbed')</label>
                            </span>
                            <span class="bs-switcher__item">
                                <input type="radio" id="radioprivateHeader" value="private" name="searchCriteria[roomType]">
                                <label for="radioprivateHeader">@langGet('bookingProcess.searchCriteria.privateroom')</label>
                            </span>
                        </div>
                    </div>
                </div>

                <div id="header-search-form__guests" class="delimiter-line search-dropdown-wrap">
                    <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Add guests
                    </button>
                    <div class="dropdown-menu p-3 p-lg-5" aria-labelledby="dropdownMenuButton">
                        <div class="" id="searchGuestsOptionsHeader">
                            <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                                <label for="bookingSearchPeopleHeader" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.people')</label>
                                <div class="d-flex align-self-start">
                                    <div class="btn btn-items btn-items-decrease">-</div>
                                    <input type="text" id="bookingSearchPeopleHeader" value="1" name="searchCriteria[people]" disabled class="form-control input-items" data-min="1">
                                    <div class="btn btn-items btn-items-increase">+</div>
                                </div>
                            </div>

                            <div class="bookingSearchGroup bookingSearchGroupTypeHeaderWrap d-flex flex-column justify-content-center align-items-start mt-3 mb-3">
                                <label for="bookingSearchGroupTypeHeader" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.groupType')</label>
                                <select class="form-control" name="searchCriteria[groupType]" id="bookingSearchGroupTypeHeader">
                                    @foreach (langGet('bookingProcess.searchCriteria.options.groupType') as $value => $text)
                                        <option value="{{{ $value }}}">{{{ $text }}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="bookingSearchGroup bookingSearchAgeRangesHeaderWrap d-flex flex-column justify-content-center align-items-start">
                                <label class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.groupAgeRanges')</label>
                                <ul class="list-unstyled w-100 mb-0">
                                    @foreach (langGet('bookingProcess.searchCriteria.options.groupAgeRanges') as $value => $text)
                                        <li class="checkbox mb-2">
                                            <div class="custom-control custom-checkbox custom-checkbox-2">
                                                <label class="custom-control-label">
                                                    {{{ $text }}}
                                                    <input type="checkbox" name="searchCriteria[groupAgeRanges][]"
                                                           class="custom-control-input" value="{{{ $value }}}">
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="header-search-form__rooms" class="search-dropdown-wrap bookingSearchRooms">
                    <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Add rooms
                    </button>
                    <div class="dropdown-menu p-3 p-lg-5" aria-labelledby="dropdownMenuButton">
                        <div class="searchRoomsOptions">
                            <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                                <label for="bookingSearchRoomsHeader" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.rooms')</label>
                                <div class="d-flex align-self-start">
                                    <div class="btn btn-items btn-items-decrease">-</div>
                                    <input type="text" id="bookingSearchRoomsHeader" value="1" name="searchCriteria[rooms]"
                                           disabled class="form-control input-items" data-min="1">
                                    <div class="btn btn-items btn-items-increase">+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="header-search-form__search" class="">
                    <button type="submit" class="btn btn-primary cl-light rounded header-search bookingSubmitButton" style="fill: #FAFBFE;">
                        @include('partials.svg-icon', ['svg_id' => 'search-icon', 'svg_w' => '24', 'svg_h' => '24'])
                    </button>
                </div>

                {{-- Set by javascript based on the actual currency input elsewhere on the page --}}
                <input name="searchCriteria[currency]" type="hidden">

            </form>
        </div>
    </div>
</div>