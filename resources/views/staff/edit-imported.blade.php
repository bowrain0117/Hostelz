@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                @if (!$formHandler->model->hostelID)
                    <li><a class="objectCommandPostFormValue" data-object-command="createListing" href="#">Create Listing</a></li>  
                @endif
                @if ($formHandler->model->web != '')
                    <li><a target="_blank" href="{!! $formHandler->model->web !!}">{{{ $formHandler->model->web }}}</a></li>
                @endif
                @if ($formHandler->model->urlLink != '')
                    <li><a target="_blank" href="{!! $formHandler->model->urlLink !!}">Booking System's Website</a></li>
                @endif
            </ul>
        </div>
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>

            @if ($formHandler->model->hostelID)
                <a href="{!! routeURL('staff-listings', [ $formHandler->model->hostelID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!} Listing</a>
            @else
                <a href="#" class="list-group-item disabled">(No listing &mdash; Use the link above to "Create Listing")</a>
            @endif

            <a href="{!! Lib\FormHandler::searchAndListURL('staff-pics', [ 'subjectType' => 'imported', 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Pic') !!} Pics</a>

            <a href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'imported', 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.AttachedText') !!} Attached Text</a>

            <a href="{!! routeURL('checkImport', [ $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>Check Import Data</a>

            <a href="{!! routeurl('forceUpdateImportedPics', [ $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>Force Update Pics</a>

        </div>
        
    @endif

@stop


@section('belowForm')

    {{--
    @if ($formHandler->mode == 'searchAndList')
        
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>
            
    @elseif ($formHandler->mode == 'editableList')
                
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>

    @endif
    --}}
    
@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'hostelID', 'placeholderText' => "Search by listing ID, name, or city." ])
    
    @parent

@endsection
