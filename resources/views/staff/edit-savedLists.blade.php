@extends('staff/edit-layout')

@section('aboveForm')

@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            @if ($formHandler->model->user_id && auth()->user()->hasPermission('staffEditUsers'))
                <a href="{!! routeURL('staff-users', [ $formHandler->model->user_id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
            @endif
        </div>
        
    @endif
                        
@stop


@section('belowForm')
    
@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'user_id', 'placeholderText' => "Search by username or name." ])
    
    @parent

@endsection
