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
        </div>

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
        </div>

        <input name="searchCriteria[currency]" type="hidden">

    </form>
</div>

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