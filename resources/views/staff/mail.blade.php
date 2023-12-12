<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts/admin')

@section('title', 'Mail')

@section('header')
    <style>
        .messagePageAlert {
            font-size: 18px;
        }
    
        .messagePageAlert i {
            font-size: 30px; 
            margin-right: 12px;
        }
    </style>
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {{-- (this started causing errors on the mail attachments page, not sure why that changed)  breadcrumb('Mail', routeURL(Route::currentRouteName()))  --}}
        </ol>
    </div>
    
    <div class="container">
    
        @if (@$displayMessagePage != '')
        
            <br><div class="alert alert-success messagePageAlert">{!! $displayMessagePage !!}</div>
            
        @elseif (@$displayErrorPage != '')
        
            <br><div class="alert alert-danger messagePageAlert">{!! $displayErrorPage !!}</div>
        
        @elseif (@$fileList)
        
            <h2>Attachments</h2>
        
            @include('Lib/fileListHandler')
            <h3>Upload</h3>
            @include('Lib/fileUploadHandler')
        
        @else
            
            @if ($formHandler->mode == 'searchAndList')
                <div class="pull-right">
                    <a href="{!! currentUrlWithQueryVar(['command' => 'spamicityEvaluate']) !!}">Re-evaluate Spam Scores of Messages on This Page</a>
                </div>
            @endif
                    
            @if ($formHandler->model && $formHandler->model->status == 'outgoingQueue' && $formHandler->mode == 'updateForm')
                
                <form method="post" class="formHandlerForm form-horizontal"> 
                
                    @include('Lib/formHandler/doEverything', [ 'itemName' => 'Mail', 'horizontalForm' => true, 'addOpenFormTag' => false, 'addCloseFormTag' => false ])
                    
                    <br>
                    <div class="text-center">                            
                        {{-- Note: We have to display our own delete button because we don't want a generic FormHandler one because 
                        we can't allow users to delete any email they want, so we use "command" and handle deletes ourselves. --}}

                        <button class="btn btn-danger submit" name="command" value="delete" type="submit" onClick="javascript:return confirm('Delete.  Are you sure?')">Delete</button>
                        <button class="btn btn-primary submit" name="command" value="sendNow" type="submit"><span class="glyphicon glyphicon-send" aria-hidden="true"></span> &nbsp; Send Now</button>

                        {{--<br><br><p><a href="{!! routeURL('staff-mail-editAttachments', $formHandler->model->id) !!}" class="underline">Upload Attachments</a></p>--}}

                        @if($editAttachments)
                            {!! $editAttachments !!}
                        @endif

                        <h3>Queued to be delivered {!! $formHandler->model->transmitTime->diffForHumans() !!}.</h3>
                    </div>
                    
                </form>
                
            @else
                
                @include('Lib/formHandler/doEverything', [ 'itemName' => 'Mail', 'horizontalForm' => true, 'showSearchOptionsByDefault' => false ])

            @endif
            
        @endif
    
    </div>

@stop


@section('pageBottom')

    @if (@$formHandler) 
        @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name.", 'minCharacters' => 0 ])
        @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'listingID', 'placeholderText' => "Search by listing ID, name, or city." ])
    @endif
    
    @parent

@endsection
