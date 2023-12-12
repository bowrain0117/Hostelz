<div class="bookingSearchForm d-flex flex-row bk-search bk-search--index justify-content-between">

    <div class="my-lg-5">
        <label class="pre-title cl-subtext" for="searchDate">Add dates</label>
        <div class="datepicker-container datepicker-container-left input-icon input-calendar">
            <input type="text" id="searchDateHeroTitle" class="form-control cursor-pointer" >
            <input type="hidden" id="searchDateHero" class="form-control">
            <input type="hidden" name="searchCriteriaHero[startDate]" id="bookingSearchDateHero" class="form-control">
            <input type="hidden" name="searchCriteriaHero[nights]" id="bookingSearchNightsHero" class="form-control">
        </div>
    </div>

    <div class="my-lg-5">
        <label class="pre-title cl-subtext" for="">Room type</label>
        <div class="bookingSearchRoomType bs-switcher mb-2 mb-lg-0">
            <span class="bs-switcher__item">
                <input type="radio" id="radiodorm" value="dorm" name="searchCriteriaHero[roomType]">
                <label for="radiodorm">Dorm Bed</label>
            </span>
            <span class="bs-switcher__item">
                <input type="radio" id="radioprivate" value="private" name="searchCriteriaHero[roomType]">
                <label for="radioprivate">Private Room</label>
            </span>
        </div>
    </div>

    <div class="my-lg-5 bookingSearchRooms d-none">
        <label class="pre-title cl-subtext" for="bookingSearchRooms">{{ __('bookingProcess.searchCriteria.rooms') }}</label>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <div class="btn btn-items btn-items-decrease font-weight-bold">-</div>
                <input type="number" id="bookingSearchRooms" value="1" name="searchCriteriaHero[rooms]" disabled class="form-control input-items" data-min="1">
                <div class="btn btn-items btn-items-increase font-weight-bold">+</div>
            </div>
        </div>
    </div>

    <div class="my-lg-5">
        <label class="pre-title cl-subtext" for="bookingSearchPeopleHero">{{ __('bookingProcess.searchCriteria.people') }}</label>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <div class="btn btn-items btn-items-decrease font-weight-bold">-</div>
                <input type="number" id="bookingSearchPeopleHero" value="1" name="searchCriteriaHero[people]" disabled class="form-control input-items" data-min="1">
                <div class="btn btn-items btn-items-increase font-weight-bold">+</div>
            </div>
        </div>
    </div>

{{--    <div class="bookingSearchRooms my-lg-5">
        <button id="searchRooms" type="button" class="form-control" data-title="@langGet('bookingProcess.searchCriteria.rooms')">@langGet('bookingProcess.searchCriteria.rooms')</button>
        <div class="bk-search__options">
            <div class="d-flex justify-content-between align-items-center">
                <label for="">@langGet('bookingProcess.searchCriteria.rooms')</label>
                <div class="d-flex align-items-center">
                    <div class="btn btn-items btn-items-decrease font-weight-bold">-</div>
                    <input type="number" id="bookingSearchRooms" value="1" name="searchCriteriaHero[rooms]"
                           disabled class="form-control input-items" data-min="1">
                    <div class="btn btn-items btn-items-increase font-weight-bold">+</div>
                </div>
            </div>

--}}{{--            <div class="d-flex justify-content-between mt-2">--}}{{--
--}}{{--                <button class="btn btn-warning bk-search__clear" type="button">@langGet('bookingProcess.searchCriteria.clear')</button>--}}{{--
--}}{{--                <button class="btn btn-success bk-search__save" type="button">@langGet('bookingProcess.searchCriteria.save')</button>--}}{{--
--}}{{--            </div>--}}{{--
        </div>--}}
    </div>
</div>


@section('pageBottom')

    <div class="modal fade" id="searchDateModalHero" tabindex="-1" role="dialog" aria-labelledby="searchDateModalHero" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-datepicker modal-dialog-centered position-relative justify-content-center" role="document">
            <div class="modal-content">
                <div class="modal-body datepicker-modal d-flex justify-content-center" style="min-width: 800px;"></div>
            </div>
        </div>
    </div>

    @include('_setDatePickerLanguage')

    @parent

@stop
