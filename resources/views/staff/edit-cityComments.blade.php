@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                @if ($formHandler->model->cityInfo)
                    <li><a href="{!! $formHandler->model->cityInfo->getURL() !!}">View City</a></li>
                @endif
            </ul>
        </div>
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
            <a href="{!! routeURL('staff-cityInfos', [ $formHandler->model->cityID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.CityInfo') !!} {!! langGet('Staff.databaseModelNames.CityInfo') !!}</a>
            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($formHandler->model->userID)
                    <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @endif
        </div>
        
    @endif
                        
@stop

@section('belowForm')

    @if ($formHandler->mode == 'updateForm')
        <br>
        <div class="row">
            <div class="col-md-10 text-center">
                {{-- (Handled by javascript in edit-layout.blade.php) --}}
                <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="approved">Approve</button>
                <button class="btn btn-info setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="flagged">Flag</button>
                <button class="btn btn-danger setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="removed">Remove</button>
            </div>
        </div>
    @endif
    

    {{--
    @if ($formHandler->mode == 'searchAndList')
        
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>
            
    @elseif ($formHandler->mode == 'editableList')
                
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>

    @endif
    --}}
    
@stop

@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'cityID', 'placeholderText' => "Search by city." ])
    
    @parent

@endsection
