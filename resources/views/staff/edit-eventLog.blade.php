@extends('staff/edit-layout')


@section('nextToForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
                
            @if ($formHandler->model->subjectEditFormURL() !== '')
                <a href="{!! $formHandler->model->subjectEditFormURL() !!}" class="list-group-item"><span class="pull-right">&raquo;</span>Subject: {!! $formHandler->model->subjectType !!}</a>
            @endif
            
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

    @if ($formHandler->mode == 'searchAndList' && auth()->user()->hasPermission('admin'))
        @if (Request::input('command') != 'analysis' && (isset($formHandler->whereData['userID']) || isset($formHandler->inputData['userID'])))
            <a href="{!! currentUrlWithQueryVar([ 'command' => 'analysis']) !!}">Analyze Work Sessions & Pay Rate</a>
        @endif
    @endif

@stop


@section('pageBottom')
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])

    @parent
    
@endsection
