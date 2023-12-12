{{--

Input:
    $pageType - 'city' or 'listing'.

--}}

<?php 
    use Lib\HttpAsset;

    HttpAsset::requireAsset('jquery-ui'); // for the datepicker
//    HttpAsset::requireAsset('booking.css');
?>

@section('header')

    @parent
    
@stop

<div class="bookingSearchAndResults">
    
    <form class="bookingSearchForm">
    
    <div class="row">
        <div class="col-xl-3 col-md-4 mb-4">
            <div class="form-group">
    			<label for="bookingSearchDate">@langGet('bookingProcess.searchCriteria.startDate')</label>
    			<div class="input-group bookingSearchDate">
                    <input id="bookingSearchDate" class="form-control">
                    <input type="hidden" name="searchCriteria[startDate]">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
                    </span>
                </div>
    		</div>
    	</div>
    	
    	<div class="col-xl-3 col-md-3 mb-4">
            <div class="form-group">
    			<label for="bookingSearchNights">@langGet('bookingProcess.searchCriteria.nights')</label>
                <div class="input-group plusMinus btn-group">
                    <button class="btn btn-primary" type="button">-</button>
                    <input id="bookingSearchNights" type="text" class="form-control" name="searchCriteria[nights]" min=1 max={!! App\Booking\SearchCriteria::MAX_NIGHTS !!}>
                    <button class="btn btn-primary" type="button">+</button>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-4 mb-4 bookingSearchRoomType fancyRadioButtons">
            <div class="form-group">
    			<label>@langGet('bookingProcess.searchCriteria.roomType')</label>
    			<div>
                    <div class="btn-group" data-toggle="buttons">
                        <div class="custom-control custom-radio">
                            <input type="radio" name="searchCriteria[roomType]" checked value="dorm" class="custom-control-input" id="radiodorm">
                            <label class="btn btn-default custom-control-label" for="radiodorm">Dorm Bed</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" name="searchCriteria[roomType]" value="private" class="custom-control-input" id="radioprivate">
                            <label class="btn btn-default custom-control-label" for="radioprivate">Private Room</label>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    
        <div class="col-xl-3 col-md-3 mb-4">
            <div class="form-group">
    			<label for="bookingSearchPeople">@langGet('bookingProcess.searchCriteria.people')</label>
                <div class="input-group plusMinus btn-group">
                    <button class="btn btn-primary" type="button">-</button>
                    <input id="bookingSearchPeople" type="text" class="form-control" name="searchCriteria[people]" min=1 max={!! App\Booking\SearchCriteria::MAX_PEOPLE !!}>
                    <button class="btn btn-primary" type="button">+</button>
                </div>
            </div>
        </div>
        
        <div class="bookingSearchRooms col-xl-3 col-md-3 mb-4">
            <div class="form-group">
    			<label for="bookingSearchRooms">@langGet('bookingProcess.searchCriteria.rooms')</label>
                <div class="input-group plusMinus btn-group">
                    <button class="btn btn-primary" type="button">-</button>
                    <input id="bookingSearchRooms" type="text" class="form-control" name="searchCriteria[rooms]" min=1 max={!! App\Booking\SearchCriteria::MAX_ROOMS !!}>
                    <button class="btn btn-primary" type="button">+</button>
                </div>
            </div>
        </div>
        
        <div class="bookingSearchGroup col-xl-9 col-md-6 mb-4">
            <div class="row">
                <div class="bookingSearchGroupType col-xl-3 col-md-3">
                    <div class="form-group">
                        <label for="bookingSearchGroupType">@langGet('bookingProcess.searchCriteria.groupType')</label>
                        <select class="form-control" name="searchCriteria[groupType]" id="bookingSearchGroupType">
                            @foreach (langGet('bookingProcess.searchCriteria.options.groupType') as $value => $text)
                                <option value="{{{ $value }}}">{{{ $text }}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bookingSearchGroupAgeRanges col-xl-9 col-md-9">
                    <div class="form-group">
                        <label>@langGet('bookingProcess.searchCriteria.groupAgeRanges')</label>
                        <ul class="btn-toolbar list-inline">
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
                </div>
            </div>
        </div>
        
        <input name="searchCriteria[currency]" type="hidden"> {{-- Set by javascript based on the actual currency input elsewhere on the page --}}
            
    	<div class="col-12 my-4">
            <button type="button" {{-- (keeps it from submitting the form automatically) --}} class="btn btn-primary disabled bookingSubmitButton">@langGet('bookingProcess.CheckAvailability')</button>
        </div>
        
    </div>
    
    </form>

    @if ($pageType == 'listing')
        <div id="bookingSearchResult"></div>
    @endif

</div>

@section('pageBottom')
    
    @include('_setDatePickerLanguage')
    
    @parent
    
@stop
