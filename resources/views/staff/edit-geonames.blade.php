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

        </div>
        
    @endif
                        
@stop


@section('belowForm')
    
@stop


@section('pageBottom')
    @parent
@endsection
