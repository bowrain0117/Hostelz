@extends('staff/edit-layout', [ 'showCreateNewLink' => false ])


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">

            </ul>
        </div>

    @endif
        
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            @if ($formHandler->model->placeType != '')
                <a href="{!! $formHandler->model->placeURL() !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! $formHandler->model->placeDisplayName() !!}</a>
            @endif
        </div>
        
    @endif
                        
@stop


@section('belowForm')

@stop

@section('pageBottom')
    
    @include('partials/_placeFieldsSelectorEnable')
        
    @parent

@stop
