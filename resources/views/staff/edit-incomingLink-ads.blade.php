<?php 
    Lib\HttpAsset::requireAsset('staff.css'); 
    
    $ad = $formHandler->model;
?>

@extends('layouts/admin')

@section('title', "Incoming Link Ads")

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            @breadcrumb(langGet('global.Home'), routeURL('home'))
            @breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu'))
            @breadcrumb('Staff', routeURL('staff-menu'))
            @breadcrumb('Incoming Link', routeURL('staff-incomingLinks', $incomingLink->id))
            
            @if ($formHandler->mode == 'list')
                @breadcrumb('Ads')
            @else
                @breadcrumb('Ads', routeURL('staff-incomingLinkAds', $incomingLink->id))
                @breadcrumb('Ad')
            @endif
        </ol>
    </div>
    
    <div class="container">
    
        {{-- Icon --}}
        
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Ad', '') !!}</div>
                    
        {{-- Form/Results --}}
        
        <div class="staffForm">
            @if ($formHandler->mode != 'list')
                <div class="row">
                    <div class="col-md-10">
                    
                        {{-- Error / Info Messages --}}
                        
                        @if (@$message != '')
                            <br><div class="well">{!! $message !!}</div>
                        @endif
                        
                        {{-- Photos --}}
                        
                        @if (in_array($formHandler->mode, [ 'updateForm', 'update', 'display', 'insert' ]))
                            {{--
                            <div class="navLinksBox">
                                <ul class="nav nav-pills">
                    
                                </ul>
                            </div>
                            --}}
                        
                            @if (@$fileList)
                                <h1>Images</h1>
                                @include('Lib/fileListHandler', [ 'fileListMode' => 'photos', 'fileListShowStatus' => true ])
                                
                                <h3>Upload New Photos</h3>
                                @include('Lib/fileUploadHandler')
                            @endif
                        
                        @elseif ($formHandler->mode == 'insertForm')
                            <p>(You will be able to add photos after you create the ad.)</p>
                        @endif        
                        
                        @include('Lib/formHandler/doEverything', [ 'itemName' => 'Ad', 'horizontalForm' => true ])
                        
                        @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update')
                            <p><a class="objectCommandPostFormValue btn btn-info btn-xs" data-object-command="duplicate" href="#">Make Duplicate Ad</a></p>
                        @elseif ($formHandler->mode == 'update')
                            <p><a href="/{!! Request::path() !!}">Back to edit the form</a></p>
                        @elseif ($formHandler->mode == 'insert')
                            <p><a href="{!! $ad->id !!}">Edit this ad</a></p>
                        @endif
                    </div>
                    
                    <div class="col-md-2">
                        
                        @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
                        
                            <div class="list-group">
                                <a href="#" class="list-group-item active">Related</a>
                                @if ($formHandler->model->placeType != '')
                                    <a href="{!! $formHandler->model->placeURL() !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! $formHandler->model->placeDisplayName() !!}</a>
                                @endif
            
                                @if (auth()->user()->hasPermission('staffEditUsers'))
                                    @if ($ad->userID)
                                        <a href="@routeURL('staff-users', [ $ad->userID ])" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                                    @else
                                        <a href="#" class="list-group-item disabled">(No user ID)</a>
                                    @endif
                                @endif
                                @if ($ad->incomingLinkID)
                                    <a href="@routeURL('staff-incomingLinks', $ad->incomingLinkID)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.IncomingLink') !!} Incoming Link</a>
                                @endif
                                @if (auth()->user()->hasPermission('admin'))
                                    <a href="@routeURL('staff-ads', $ad->id)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Ad') !!} Edit Ad</a>
                                @endif
                            </div>
                            
                        @endif

                        @if ($adsForTheSamePlace && !$adsForTheSamePlace->isEmpty()) 
                            <div class="list-group">
                                <a href="#" class="list-group-item active">Other Ads for Same Place</a>
                                @foreach ($adsForTheSamePlace as $otherAd)
                                    <a href="@routeURL('staff-ads', $otherAd->id)" class="list-group-item">{{{ $otherAd->linkURL }}} ({!! $otherAd->status !!})</a>
                                @endforeach
                            </div>
                        @endif   
                        
                    </div>
                </div>
            @else
            
                @include('Lib/formHandler/doEverything', [ 'itemName' => 'Ad', 'horizontalForm' => isset($horizontalForm) ? $horizontalForm : true ])
                
            @endif
        </div>

    </div>

@stop


@section('pageBottom')

    @include('Lib/_postFormValue', [ 'valueName' => 'objectCommand' ])

    @include('partials/_placeFieldsSelectorEnable')
    
    @parent

@stop
