@section('header')

    @parent

@stop

<section class="mb-3 mt-2 selected-filters container-search-wrap shadow-1 @if (isset($listingFilters)) bg-gray-800 @endif py-2">
    <div class="logo-search">
        <a href="{!! routeURL('home') !!}" class="navbar-brand py-1"><img src="{!! routeURL('images', 'logo-hostelz-white.png') !!}" alt="Hostelz.com - the worlds largest hostel database" height="21px"></a>
    </div>

    <div class="container container-search">
        <form class="bookingSearchForm d-flex flex-row bk-search">

            <div class="m-lg-2 m-1 bk-search__item">
                <div class="datepicker-container datepicker-container-left">
                    <input type="text" id="searchDate" placeholder="<?php echo date('d  M. Y'); ?>" class="form-control bk-search__btn">
                    <input type="hidden" name="searchCriteria[startDate]" id="bookingSearchDate" class="form-control">
                    <input type="hidden" name="searchCriteria[nights]" id="bookingSearchNights" class="form-control">
                </div>
            </div>

            <div class="m-lg-2 m-1 bk-search__item">
                <button id="searchGuests" type="button" class="form-control bk-search__btn" data-title="@langGet('bookingProcess.searchCriteria.people')">@langGet('bookingProcess.searchCriteria.people')</button>
                <div class="bk-search__options bk-search__options--large">
                    <div class="d-flex justify-content-between align-items-center bk-search__wrap mb-2">
                        <label for="bookingSearchPeople">@langGet('bookingProcess.searchCriteria.people')</label>
                        <div class="d-flex align-items-center">
                            <div class="btn btn-items btn-items-decrease">-</div>
                            <input type="number" id="bookingSearchPeople" value="1" name="searchCriteria[people]" disabled class="form-control input-items" data-min="1">
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
            </div>

            <div class="m-lg-2 m-1 bk-search__item bookingSearchRoomType">
                <button id="searchRoomType" type="button" class="form-control bk-search__btn bk-search__btn-active" data-title="@langGet('bookingProcess.searchCriteria.roomType')">@langGet('bookingProcess.searchCriteria.roomType')</button>
                <div class="bk-search__options">
                    <ul class="list-unstyled mb-0">
                        <li>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="radiodorm" value="dorm" name="searchCriteria[roomType]" class="custom-control-input">
                                <label for="radiodorm" class="custom-control-label">@langGet('bookingProcess.searchCriteria.dormbed')</label>
                            </div>
                        </li>
                        <li>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="radioprivate" value="private" name="searchCriteria[roomType]" class="custom-control-input">
                                <label for="radioprivate" class="custom-control-label">@langGet('bookingProcess.searchCriteria.privateroom')</label>
                            </div>
                        </li>
                    </ul>

                    <div class="d-flex justify-content-end mt-2">
                        <button class="btn btn-success bk-search__save" type="button">@langGet('bookingProcess.searchCriteria.save')</button>
                    </div>

                </div>
            </div>

            <div class="m-lg-2 m-1 bk-search__item bookingSearchRooms">
                <button id="searchRooms" type="button" class="form-control bk-search__btn" data-title="@langGet('bookingProcess.searchCriteria.rooms')">@langGet('bookingProcess.searchCriteria.rooms')</button>
                <div class="bk-search__options">
                    <div class="d-flex justify-content-between align-items-center bk-search__wrap">
                        <label for="">@langGet('bookingProcess.searchCriteria.rooms')</label>
                        <div class="d-flex align-items-center">
                            <div class="btn btn-items btn-items-decrease">-</div>
                            <input type="number" id="bookingSearchRooms" value="1" name="searchCriteria[rooms]"
                                   disabled class="form-control input-items" data-min="1">
                            <div class="btn btn-items btn-items-increase">+</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <button class="btn btn-warning bk-search__clear" type="button">@langGet('bookingProcess.searchCriteria.clear')</button>
                        <button class="btn btn-success bk-search__save" type="button">@langGet('bookingProcess.searchCriteria.save')</button>
                    </div>
                </div>
            </div>

            {{-- Set by javascript based on the actual currency input elsewhere on the page --}}
            <input name="searchCriteria[currency]" type="hidden">

            @if (isset($listingFilters))
            <div class="m-lg-2 m-1 bk-search__item">
                <button id="searchFilters" type="button" class="form-control bk-search__btn" data-toggle="modal" data-target="#bookingFiltersModal" data-title="@langGet('bookingProcess.searchCriteria.morefilter')">@langGet('bookingProcess.searchCriteria.morefilter')</button>

                <div id="bookingFiltersModal" tabindex="-1" role="dialog" aria-labelledby="bookingFiltersModal" aria-hidden="true" class="modal fade">
                    <div role="document" class="modal-dialog 123">
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

    @include('_setDatePickerLanguage')

    @parent

@stop
