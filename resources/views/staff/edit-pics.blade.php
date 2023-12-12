@extends('staff/edit-layout')

@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        @if ($formHandler->model->storageTypes)
            <div class="fileList"> 
                @foreach ($formHandler->model->storageTypes as $sizeType => $storageType)
                    <div class="thumbnail text-center">
                        <a href="{!! $formHandler->model->url([ $sizeType ]) !!}"><img src="{!! $formHandler->model->url([ $sizeType ]) !!}"></a>
                        <div class="caption">{{{ $sizeType }}} ({!! $storageType !!})</div>
                    </div>
                @endforeach                
            </div>
        @endif

        {{--  (dont know which size type to pass to the URL... disabling this for now)
        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="https://www.tineye.com/search?url={!! urlencode('https://' . config('custom.publicStaticDomain') . $formHandler->model->url([ 'big' ])) !!}">TinEye Search</a></li>
            </ul>
        </div>
        --}}
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            @if ($formHandler->model->subjectType == 'hostels')
                <a href="{!! routeURL('staff-listings', [ $formHandler->model->subjectID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>Listing</a>
            @else
                <a href="{!! routeURL('staff-'.$formHandler->model->subjectType.(Str::endsWith($formHandler->model->subjectType, 's') ? '' : 's'), [ $formHandler->model->subjectID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! ucwords(Str::singular($formHandler->model->subjectType)) !!}</a>
            @endif
            @if ($formHandler->model->source && auth()->user()->hasPermission('staffEditUsers'))
                <a href="{!! routeURL('staff-users', [ $formHandler->model->source ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User (from source)</a>
            @endif
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
