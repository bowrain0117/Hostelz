<?php
    $incomingLink = $formHandler->model;
?>

@extends('staff/edit-layout', [ 'showCreateNewLink' => false ])


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                @if ($formHandler->model->url != '')
                    <li><a href="{{{ $formHandler->model->url }}}" rel="noreferrer" target="_blank">{{{ $formHandler->model->url }}}</a></li>
                    <li><a class="objectCommandPostFormValue" data-object-command="updateLinkInformation" href="#">Update Link Info</a></li>
                @endif
            </ul>
        </div>
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            
            @if (auth()->user()->hasPermission('admin'))
                @if (Request::route()->getName() == 'staff-incomingLinks')
                    <a href="@routeURL('staff-incomingLinksEdit', $incomingLink->id)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.IncomingLink') !!} Admin Edit</a>
                @else
                    <a href="@routeURL('staff-incomingLinks', $formHandler->model->id)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.IncomingLink') !!} Contact Form</a>
                @endif
            @endif
            
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
            @if ($formHandler->model->contactEmails)
                @foreach ($formHandler->model->contactEmails as $email)
                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' => $email, 'spamFilter' => false ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!} {{{ count($formHandler->model->contactEmails) == 1 ? '' : "\"$email\"" }}} Emails</a>
                @endforeach
            @endif
            
            @if ($formHandler->model->placeType != '')
                <a href="{!! $formHandler->model->placeURL() !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! $formHandler->model->placeDisplayName() !!}</a>
            @endif
            
            @if (auth()->user()->hasPermission('staffMarketingLevel2'))
                <a href="@routeURL('staff-incomingLinkAds', $formHandler->model->id)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Ad') !!} Ads</a>
            @endif
        
            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($incomingLink->userID)
                    <a href="{!! routeURL('staff-users', [ $incomingLink->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} {{{ $incomingLink->user->username }}}</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @elseif ($incomingLink->userID && $incomingLink->userID != auth()->id())
                <a href="#" class="list-group-item disabled">{!! langGet('Staff.icons.User') !!} This link currently belongs to {{{ $incomingLink->user->username }}}</a>
            @endif
            
        </div>
        
    @endif
    
@stop


@section('belowForm')

@stop


@section('pageBottom')
    
    @include('partials/_placeFieldsSelectorEnable')
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name.", 'minCharacters' => 0 ])
    
    @parent

@stop
