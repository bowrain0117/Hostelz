@section('header')

    @parent

@stop

<section class="mb-3 mt-2 selected-filters container-search-wrap py-2" id="availability">
    <div class="container-search">
        <form class="bookingSearchFormSecond d-flex flex-row justify-content-between bk-search tx-small cl-text">

            {{-- date --}}
            <div class="flex-shrink-0 bk-search__item d-flex align-items-center mb-2 mb-lg-0 bookingSearchFormSecond-date">
                <span id="searchDateContent" class="cursor-pointer">
                    <span class="mr-1">@include('partials.svg-icon', ['svg_id' => 'calendar', 'svg_w' => '24', 'svg_h' => '24'])</span>
                    <span class="_date text-decoration-underline"></span>
                </span>
                <input type="hidden" name="searchCriteria[startDate]" id="bookingSearchDate" class="form-control">
                <input type="hidden" name="searchCriteria[nights]" id="bookingSearchNights" class="form-control">
            </div>

            {{-- roomType --}}
            <div class="flex-shrink-0 bk-search__item bookingSearchRoomType bs-switcher mb-2 mb-lg-0 bookingSearchFormSecond-roomType">
                <span class="bs-switcher__item">
                    <input type="radio" id="radiodorm" value="dorm" name="searchCriteriaSecond[roomType]">
                    <label for="radiodorm">@langGet('bookingProcess.searchCriteria.dormbed')</label>
                </span>
                <span class="bs-switcher__item">
                    <input type="radio" id="radioprivate" value="private" name="searchCriteriaSecond[roomType]">
                    <label for="radioprivate">@langGet('bookingProcess.searchCriteria.privateroom')</label>
                </span>
            </div>

            {{-- guests --}}
            <div id="searchGuestsSecond" class="flex-shrink-0 d-flex align-items-center justify-content-center position-relative mb-2 mb-lg-0 bookingSearchFormSecond-guests">
                @include('partials.svg-icon', ['svg_id' => 'guests-icon', 'svg_w' => '24', 'svg_h' => '24'])
                <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    @langGet('bookingProcess.searchCriteria.people')
                </button>
                <div class="dropdown-menu p-3 p-lg-5" aria-labelledby="dropdownMenuButton">
                    <div class="">
                        <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                            <label for="bookingSearchPeopleSecond" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.people')</label>
                            <div class="d-flex align-self-start">
                                <div class="btn btn-items btn-items-decrease">-</div>
                                <input type="text" id="bookingSearchPeopleSecond" value="1" name="searchCriteria[people]" disabled class="form-control input-items" data-min="1">
                                <div class="btn btn-items btn-items-increase">+</div>
                            </div>
                        </div>

                        <div class="bookingSearchGroup bookingSearchGroupTypeSecondWrap d-flex flex-column justify-content-center align-items-start mt-3 mb-3">
                            <label for="bookingSearchGroupTypeSecond" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.groupType')</label>
                            <select class="form-control" name="searchCriteria[groupType]" id="bookingSearchGroupTypeSecond">
                                @foreach (langGet('bookingProcess.searchCriteria.options.groupType') as $value => $text)
                                    <option value="{{{ $value }}}">{{{ $text }}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bookingSearchGroup bookingSearchAgeRangesSecondWrap">
                            <label class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.groupAgeRanges')</label>
                            <ul class="list-unstyled mb-0">
                                @foreach (langGet('bookingProcess.searchCriteria.options.groupAgeRanges') as $value => $text)
                                    <li class="checkbox mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" id="_id-sec-{{{ $loop->index }}}" name="searchCriteria[groupAgeRanges][]"
                                                   class="custom-control-input" value="{{{ $value }}}">
                                            <label for="_id-sec-{{{ $loop->index }}}" class="custom-control-label">{{{ $text }}}</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>


{{--

                <div id="searchGuests" class="bk-search__btn d-flex align-items-center justify-content-center flex-nowrap cursor-pointer">
                    @include('partials.svg-icon', ['svg_id' => 'guests-icon', 'svg_w' => '24', 'svg_h' => '24'])
                    <span class="ml-2 text-decoration-underline searchGuestsNumbers">1 guest</span>
                </div>

                <div class="bk-search__options" id="searchGuestsOptions">
                    <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                        <label for="bookingSearchPeople" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.people')</label>
                        <div class="d-flex align-self-start">
                            <div class="btn btn-items btn-items-decrease">-</div>
                            <input type="text" id="bookingSearchPeople" value="1" name="searchCriteria[people]" disabled class="form-control input-items" data-min="1">
                            <div class="btn btn-items btn-items-increase">+</div>
                        </div>
                    </div>
                    <div class="bookingSearchGroup d-flex flex-column justify-content-center align-items-start mt-3 mb-3">
                        <label for="bookingSearchGroupType" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.groupType')</label>
                        <select class="form-control" name="searchCriteria[groupType]" id="bookingSearchGroupType">
                            @foreach (langGet('bookingProcess.searchCriteria.options.groupType') as $value => $text)
                                <option value="{{{ $value }}}">{{{ $text }}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bookingSearchGroup d-flex flex-column justify-content-center align-items-start">
                        <label class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.groupAgeRanges')</label>
                        <ul class="list-unstyled mb-0">
                            @foreach (langGet('bookingProcess.searchCriteria.options.groupAgeRanges') as $value => $text)
                                <li class="checkbox">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" id="_id-{{{ $loop->index }}}" name="searchCriteria[groupAgeRanges][]"
                                               class="custom-control-input" value="{{{ $value }}}">
                                        <label for="_id-{{{ $loop->index }}}" class="custom-control-label">{{{ $text }}}</label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>--}}
            </div>

            {{--<div class="bk-search__item">
                <button id="searchGuests" type="button" class="form-control bk-search__btn" data-title="@langGet('bookingProcess.searchCriteria.people')">@langGet('bookingProcess.searchCriteria.people')</button>
                <div class="bk-search__options bk-search__options--large">
                    <div class="d-flex justify-content-between align-items-center bk-search__wrap mb-2">
                        <label for="bookingSearchPeople">@langGet('bookingProcess.searchCriteria.people')</label>
                        <div class="d-flex align-items-center">
                            <div class="btn btn-items btn-items-decrease">-</div>
                            <input type="text" id="bookingSearchPeople" value="1" name="searchCriteria[people]" disabled class="form-control input-items" data-min="1">
                            <div class="btn btn-items btn-items-increase">+</div>
                        </div>
                    </div>
                    <div class="bookingSearchGroup mb-2">
                        <label for="bookingSearchGroupType">@langGet('bookingProcess.searchCriteria.groupType')</label>
                        <select class="form-control" name="searchCriteria[groupType]" id="bookingSearchGroupType">
                            @foreach (langGet('bookingProcess.searchCriteria.options.groupType') as $value => $text)
                                <option value="{{{ $value }}}">{{{ $text }}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bookingSearchGroup">
                        <label>@langGet('bookingProcess.searchCriteria.groupAgeRanges')</label>
                        <ul class="btn-toolbar list-inline mb-0">
                            @foreach (langGet('bookingProcess.searchCriteria.options.groupAgeRanges') as $value => $text)
                                <li class="checkbox list-inline-item">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" id="_id-{{{ $loop->index }}}" name="searchCriteria[groupAgeRanges][]"
                                               class="custom-control-input" value="{{{ $value }}}">
                                        <label for="_id-{{{ $loop->index }}}" class="custom-control-label">{{{ $text }}}</label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <button class="btn btn-warning bk-search__clear" type="button">@langGet('bookingProcess.searchCriteria.clear')</button>
                        <button class="btn btn-success bk-search__save" type="button">@langGet('bookingProcess.searchCriteria.save')</button>
                    </div>
                </div>
            </div>--}}


            {{-- rooms --}}
            <div class="bookingSearchRooms flex-shrink-0 d-flex align-items-center justify-content-center position-relative mb-2 mb-lg-0 bookingSearchFormSecond-rooms">
                @include('partials.svg-icon', ['svg_id' => 'rooms', 'svg_w' => '24', 'svg_h' => '24'])
                <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Add rooms
                </button>
                <div class="dropdown-menu p-3 p-lg-5 dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                    <div class="searchRoomsOptions">
                        <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                            <label for="bookingSearchRoomsSecond" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.rooms')</label>
                            <div class="d-flex align-self-start">
                                <div class="btn btn-items btn-items-decrease">-</div>
                                <input type="text" id="bookingSearchRoomsSecond" value="1" name="searchCriteria[rooms]"
                                       disabled class="form-control input-items" data-min="1">
                                <div class="btn btn-items btn-items-increase">+</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{--<button id="searchRooms" type="button"
                    class="bk-search__btn d-flex align-items-center justify-content-center flex-nowrap btn-search"
                    data-title="@langGet('bookingProcess.searchCriteria.rooms')">
                        @include('partials.svg-icon', ['svg_id' => 'rooms', 'svg_w' => '24', 'svg_h' => '24'])
                        <span class="searchRooms-text ml-2 text-decoration-underline">@langGet('bookingProcess.searchCriteria.rooms')</span>
                </button>
                <div class="bk-search__options searchRoomsOptions">
                    <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                        <label for="bookingSearchRooms" class="pre-title mb-2">@langGet('bookingProcess.searchCriteria.rooms')</label>
                        <div class="d-flex align-self-start">
                            <div class="btn btn-items btn-items-decrease">-</div>
                            <input type="text" id="bookingSearchRooms" value="1" name="searchCriteria[rooms]"
                                   disabled class="form-control input-items" data-min="1">
                            <div class="btn btn-items btn-items-increase">+</div>
                        </div>
                    </div>
                </div>--}}
            </div>

            {{-- Set by javascript based on the actual currency input elsewhere on the page --}}
            <input name="searchCriteria[currency]" type="hidden">

            {{-- filtest --}}
            @if (isset($listingFilters))
                <div class="m-lg-2 m-1 bk-search__item">
                    <button id="searchFilters" type="button" class="form-control bk-search__btn" data-toggle="modal" data-target="#bookingFiltersModal" data-title="@langGet('bookingProcess.searchCriteria.morefilter')">@langGet('bookingProcess.searchCriteria.morefilter')</button>
                    <div id="bookingFiltersModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade">
                        <div role="document" class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-body">
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true">× </span></button>

                                    <div class="listingFilters">
                                        @include('bookings/_filters', [ 'listingFilters' => $listingFilters ])
                                    </div>

                                </div>
                                <div class="modal-footer justify-content-between">
                                    <button type="button" class="btn btn-warning bk-search__clear-filters">@langGet('bookingProcess.searchCriteria.clear')</button>
                                    <button type="button" data-dismiss="modal"  class="btn btn-success">@langGet('bookingProcess.searchCriteria.save')</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </form>
    </div>
</section>



@section('pageBottom')

    <div class="modal fade" id="searchDateModal" tabindex="-1" role="dialog" aria-labelledby="searchDateModal" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-datepicker modal-dialog-centered position-relative justify-content-center" role="document">
            <div class="modal-content">
                <div class="modal-body datepicker-modal d-flex justify-content-center">
                    <input type="text" id="searchDate" class="form-control bk-search__btn d-none">
                </div>
            </div>
        </div>
    </div>

    @include('_setDatePickerLanguage')

    @parent

@stop
