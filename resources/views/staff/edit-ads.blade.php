@extends('staff/edit-layout', [ 'showCreateNewLink' => false ])


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        {{--
        <div class="navLinksBox">
            <ul class="nav nav-pills">

            </ul>
        </div>
        --}}
    
        @if (@$fileList)
            <br>
            @include('Lib/fileListHandler', [ 'fileListMode' => 'photos', 'fileListShowStatus' => true ])
            
            <h3>Upload New Photos</h3>
            @include('Lib/fileUploadHandler')
        @endif
        
    @endif
        
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            @if ($formHandler->model->placeType != '')
                <a href="{!! $formHandler->model->placeURL() !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! $formHandler->model->placeDisplayName() !!}</a>
            @endif
            
            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($formHandler->model->userID)
                    <a href="@routeURL('staff-users', [ $formHandler->model->userID ])" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @endif
            
            @if ($formHandler->model->incomingLinkID)
                <a href="@routeURL('staff-incomingLinks', $formHandler->model->incomingLinkID)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.IncomingLink') !!} Incoming Link</a>
            @endif
        </div>
        
        @if ($adsForTheSamePlace && !$adsForTheSamePlace->isEmpty()) 
            <div class="list-group">
                <a href="#" class="list-group-item active">Other Ads for Same Place</a>
                @foreach ($adsForTheSamePlace as $otherAd)
                    <a href="@routeURL('staff-ads', $otherAd->id)" class="list-group-item">{{{ $otherAd->linkURL }}} ({!! $otherAd->status !!})</a>
                @endforeach
            </div>
        @endif   
        
    @endif
                        
@stop


@section('belowForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
        <a class="objectCommandPostFormValue btn btn-info btn-xs" data-object-command="duplicate" href="#">Make Duplicate Ad</a>
    @elseif ($formHandler->mode == 'searchForm' || $formHandler->mode == 'searchAndList')
        <br><br><a @if (@$formHandler->inputData['incomingLinkID']) href="/{!! Request::path() !!}/new?data[incomingLinkID]={!! $formHandler->inputData['incomingLinkID'] !!}" @else href="/{!! Request::path() !!}/new" @endif><i class="fa fa-plus-circle"></i> Create a New Ad</a>
    @endif
    
@stop

@section('pageBottom')
    
    @include('partials/_placeFieldsSelectorEnable')
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name.", 'minCharacters' => 0 ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'incomingLinkID', 'placeholderText' => "Search by incoming link URL." ])
    
    @parent

@stop
