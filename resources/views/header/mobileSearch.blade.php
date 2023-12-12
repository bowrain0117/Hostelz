<div id="searchMobileButtonWrap" class="d-block d-lg-none">
    <button class="js-open-search-location btn-clear cursor-pointer">
        @include('partials.svg-icon', ['svg_id' => 'search-icon-2', 'svg_w' => '24', 'svg_h' => '24'])
    </button>
</div>

<div id="searchIndexMobileButtonWrap" class="flex-grow-1">
    <button class="searchIndexMobileButton btn-clear cursor-pointer">
        @include('partials.svg-icon', ['svg_id' => 'search-icon-2', 'svg_w' => '24', 'svg_h' => '24']) Where are you going?
    </button>
</div>

<div id="bookingSearchFormMobile" class="d-block d-lg-none">
    {{--  mobile location  --}}
    <div class="modal right fade modal-mobile-search-location" id="modalMobileSearchLocation" tabindex="-1" role="dialog" aria-labelledby="modalMobileSearchLocation" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="d-flex justify-content-between">
                        <button class="btn-clear mobile-search-prev" style="stroke: #FF5852; margin-right: 14px;" data-dismiss="modal">
                            @include('partials.svg-icon', ['svg_id' => 'arrow-prev', 'svg_w' => '24', 'svg_h' => '24'])
                        </button>
                        <div class="position-relative searchLocationMobileWrap flex-grow-1">
                            <span class="position-absolute" style="left: 20px; top:12px; fill: #4A5268;">@include('partials.svg-icon', ['svg_id' => 'search-icon', 'svg_w' => '22', 'svg_h' => '22'])</span>

                            <input name="location" placeholder="{{{ langGet('index.EnterAName') }}}" type="text" class="searchLocationMobile form-control bg-light rounded-xl border-0 cl-subtext" style="padding-left: 54px; padding-right: 54px;" />

                            <span class="position-absolute cursor-pointer search-autocomplete-clear" style="right: 20px; top:12px; fill: #4A5268;">@include('partials.svg-icon', ['svg_id' => 'close-icon', 'svg_w' => '22', 'svg_h' => '22'])</span>
                        </div>
                    </div>
                    <div class="location-suggestion">
                        <div class="d-flex justify-content-center spinner-wrap mt-3 d-none-i">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--  mobile dates  --}}
    <div class="modal right fade" id="modalMobileSearchDates" tabindex="-1" role="dialog" aria-labelledby="modalMobileSearchDates" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body d-flex flex-column">
                    <div class="d-flex justify-content-between">
                        <button data-target="searchMobileLocationShow" class="btn-clear mobile-search-prev" style="stroke: #FAFBFE; margin-right: 14px;" data-dismiss="modal">
                            @include('partials.svg-icon', ['svg_id' => 'arrow-prev', 'svg_w' => '24', 'svg_h' => '24'])
                        </button>
                        <div class="align-items-center d-flex flex-grow-1">
                            <div class="form-title"></div>
                            <input type="text" id="searchDateMobile" class="d-none">
                        </div>
                        <button class="btn-clear searchMobileClose" style="" data-dismiss="modal">
                            @include('partials.svg-icon', ['svg_id' => 'close-icon-3', 'svg_w' => '24', 'svg_h' => '24'])
                        </button>
                    </div>
                    <div class="modalMobileSearchDatesContainer d-flex justify-content-center"></div>
                    <div class="d-flex mt-auto justify-content-between">
                        <button id="header-search-date-skip" type="button" class="btn btn-outline-light">skip dates</button>
                        <button class="searchMobileButton btn btn-lg btn-primary d-flex px-5 justify-content-center">
                            @include('partials.svg-icon', ['svg_id' => 'search-icon-3', 'svg_w' => '24', 'svg_h' => '24']) Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--  mobile guests  --}}
    <div class="modal right fade" id="modalMobileSearchGuests" tabindex="-1" role="dialog" aria-labelledby="modalMobileSearchGuests" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body d-flex flex-column">
                    <div class="d-flex justify-content-between mb-3">
                        <button data-target="searchMobileDatesShow" class="btn-clear mobile-search-prev" style="stroke: #FF5852; margin-right: 14px;" data-dismiss="modal">
                            @include('partials.svg-icon', ['svg_id' => 'arrow-prev', 'svg_w' => '24', 'svg_h' => '24'])
                        </button>
                        <div class="align-items-center d-flex flex-grow-1">
                            <div class="font-weight-600 cl-text">Room details</div>
                        </div>
                        <button class="btn-clear searchMobileClose" style="" data-dismiss="modal">
                            @include('partials.svg-icon', ['svg_id' => 'close-icon-3', 'svg_w' => '24', 'svg_h' => '24'])
                        </button>
                    </div>

                    <div id="modalMobileSearchRoomType" class=" mb-3">
                        <label for="" class="pre-title cl-subtext mb-2">Room type</label>

                        <div class="flex-shrink-0 bk-search__item bookingSearchRoomType bs-switcher">
                            <span class="bs-switcher__item">
                                <input type="radio" id="radiodormMobile" value="dorm" name="searchCriteriaMobile[roomType]">
                                <label for="radiodormMobile">@langGet('bookingProcess.searchCriteria.dormbed')</label>
                            </span>
                            <span class="bs-switcher__item">
                                <input type="radio" id="radioprivateMobile" value="private" name="searchCriteriaMobile[roomType]">
                                <label for="radioprivateMobile">@langGet('bookingProcess.searchCriteria.privateroom')</label>
                            </span>
                        </div>

                        <div class="bookingSearchRoomsMobileWrap">
                            <div class="d-flex flex-column justify-content-center align-items-start mt-3">
                                <label for="bookingSearchRoomsMobile" class="pre-title cl-subtext mb-2">@langGet('bookingProcess.searchCriteria.rooms')</label>
                                <div class="d-flex align-self-start">
                                    <div class="btn btn-items btn-items-decrease">-</div>
                                    <input type="text" id="bookingSearchRoomsMobile" value="1" name="searchCriteriaMobile[rooms]"
                                           disabled class="form-control input-items" data-min="1">
                                    <div class="btn btn-items btn-items-increase">+</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="searchGuestsOptionsMobile" class="mb-3">
                        <div class="d-flex flex-column justify-content-center align-items-start mb-3">
                            <label for="bookingSearchPeopleMobile" class="pre-title cl-subtext  mb-2">@langGet('bookingProcess.searchCriteria.people')</label>
                            <div class="d-flex align-self-start">
                                <div class="btn btn-items btn-items-decrease">-</div>
                                <input type="text" id="bookingSearchPeopleMobile" value="1" name="searchCriteriaMobile[people]" disabled class="form-control input-items" data-min="1">
                                <div class="btn btn-items btn-items-increase">+</div>
                            </div>
                        </div>

                        <div class="bookingSearchGroup bookingSearchGroupTypeMobileWrap d-flex flex-column justify-content-center align-items-start mb-3">
                            <label for="bookingSearchGroupTypeMobile" class="pre-title cl-subtext  mb-2">@langGet('bookingProcess.searchCriteria.groupType')</label>
                            <select class="form-control" name="searchCriteriaMobile[groupType]" id="bookingSearchGroupTypeMobile">
                                @foreach (langGet('bookingProcess.searchCriteria.options.groupType') as $value => $text)
                                    <option value="{{{ $value }}}">{{{ $text }}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bookingSearchGroup bookingSearchAgeRangesMobileWrap d-flex flex-column justify-content-center align-items-start">
                            <label class="pre-title cl-subtext  mb-2">@langGet('bookingProcess.searchCriteria.groupAgeRanges')</label>
                            <ul class="list-unstyled mb-0 w-100">
                                @foreach (langGet('bookingProcess.searchCriteria.options.groupAgeRanges') as $value => $text)
                                    <li class="checkbox mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" id="_id-top-{{{ $loop->index }}}" name="searchCriteriaMobile[groupAgeRanges][]"
                                                   class="custom-control-input" value="{{{ $value }}}">
                                            <label for="_id-top-{{{ $loop->index }}}" class="custom-control-label">{{{ $text }}}</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex mt-auto">
                        <button class="searchMobileButton btn btn-lg btn-primary d-flex px-5 w-100 justify-content-center">
                            @include('partials.svg-icon', ['svg_id' => 'search-icon-3', 'svg_w' => '24', 'svg_h' => '24']) Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{--  mobile room type  --}}
    {{-- <div class="modal right fade" tabindex="-1" role="dialog" aria-labelledby="modalMobileSearchRoomType" aria-hidden="true">
         <div class="modal-dialog" role="document">
             <div class="modal-content">
                 <div class="modal-body d-flex flex-column">
                     <div class="d-flex justify-content-between mb-3">
                         <button id="mobile-search-prev" class="btn-clear" style="stroke: #FF5852; margin-right: 14px;" data-dismiss="modal">
                             @include('partials.svg-icon', ['svg_id' => 'arrow-prev', 'svg_w' => '24', 'svg_h' => '24'])
                         </button>
                         <div class="align-items-center d-flex flex-grow-1">
                             <div class="font-weight-600 cl-text">Room details</div>
                         </div>
                     </div>
                     <div>
                         <label for="" class="pre-title cl-subtext mb-2">Room type</label>
                         <div class="flex-shrink-0 bk-search__item bookingSearchRoomType bs-switcher mb-3">
                         <span class="bs-switcher__item">
                             <input type="radio" id="radiodormMobile" value="dorm" name="searchCriteriaMobile[roomType]">
                             <label for="radiodormMobile">@langGet('bookingProcess.searchCriteria.dormbed')</label>
                         </span>
                             <span class="bs-switcher__item">
                             <input type="radio" id="radioprivateMobile" value="private" name="searchCriteriaMobile[roomType]">
                             <label for="radioprivateMobile">@langGet('bookingProcess.searchCriteria.privateroom')</label>
                         </span>
                         </div>
                         <div class="bookingSearchRoomsMobileWrap">
                             <div class="d-flex flex-column justify-content-center align-items-start bk-search__wrap">
                                 <label for="bookingSearchRoomsMobile" class="pre-title cl-subtext mb-2">@langGet('bookingProcess.searchCriteria.rooms')</label>
                                 <div class="d-flex align-self-start">
                                     <div class="btn btn-items btn-items-decrease">-</div>
                                     <input type="text" id="bookingSearchRoomsMobile" value="1" name="searchCriteriaMobile[rooms]"
                                            disabled class="form-control input-items" data-min="1">
                                     <div class="btn btn-items btn-items-increase">+</div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="d-flex mt-auto">
                         <button class="searchMobileButton btn btn-lg btn-primary d-flex px-5 w-100 justify-content-center">
                             @include('partials.svg-icon', ['svg_id' => 'search-icon-3', 'svg_w' => '24', 'svg_h' => '24']) Search
                         </button>
                     </div>
                 </div>
             </div>
         </div>
     </div>--}}
</div>