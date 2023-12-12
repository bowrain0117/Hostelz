@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        {{--
        <div class="navLinksBox">
            <ul class="nav nav-pills">
            </ul>
        </div>
        --}}
        
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
        </div>
        
    @endif
                        
@stop


@section('belowForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'insertForm')
        <ul>
            <li>
                <strong>Mail</strong>
                
                <ul>
                    <li>
                        <strong>Special strings:</strong>
                        <ul>
                            <li><em>General:</em> [sender], [recipients], [subject], [messageText], [quotedMessage]</li>
                            <li><em>Listing:</em> [listingBookingSystemNames], [listingBookingSystemNamesSystems], [listingEditURL]</li>
                            <li><em>Booking:</em> [bookingSystem]</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Variables:</strong>
                        <ul>
                            <li><em>User:</em> isAlreadyMgmt (true/false)</li>
                            <li><em>Listing:</em> isInActiveBookingSystems (true/false)</li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li>
                <strong>Review</strong>
                
                <ul>
                    <li>
                        <strong>Special strings:</strong>
                        <ul>
                            <li>[listingCorrectionForm], [minimumWordsAccepted], [minimumPicWidthAccepted], [minimumPicHeightAccepted]
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>

    @endif
@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name.", 'minCharacters' => 0 ])
    
    @parent

@endsection
