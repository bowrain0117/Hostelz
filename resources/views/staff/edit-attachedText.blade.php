<?php
    use App\Models\CityInfo;
    use App\Models\CountryInfo;
?>

@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="{!! $formHandler->model->urlOfSubject() !!}">{{{ $formHandler->model->nameOfSubject() }}}</a></li>
            </ul>
        </div>
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
            @if (auth()->user()->hasPermission('staffEditUsers') && $formHandler->model->userID)
                <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
            @endif

            @if ($formHandler->model->source && auth()->user()->hasPermission('staffEditUsers'))
                <a href="{!! routeURL('staff-users', [ $formHandler->model->source ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User (from source)</a>
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
                <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="ok">Approve</button>
                    <button class="btn btn-info setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="flagged">Flag</button>
                @if (auth()->user()->hasPermission('admin'))
                    {{-- For now we only allow staff the Approve ?? Flag descriptions, but not Return ?? Deny them --}}
                    <button class="btn btn-warning setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="returned">Return</button>
                    <button class="btn btn-danger setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="denied">Deny</button>
                @endif
            </div>
        </div>
        
        @if (auth()->user()->hasPermission('admin') && $formHandler->model->subjectType == 'cityInfo')
            <br>
            <div class="well">
                <p>Comment reply sample text:</p>
                <div> "City descriptions must be a minimum of 250 words.  Please add some more detail to this one." </div>
            </div>
        @endif
        
    @endif
    
@stop


@section('pageBottom')

    <script>
        showWordCount('data[data]');
    </script>
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])
    
    @parent

@endsection
