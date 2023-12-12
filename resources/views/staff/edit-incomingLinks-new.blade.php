<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts/admin')

@section('title', 'New Incoming Link')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb(langGet('Staff.databaseModelNames.IncomingLink')) !!}
        </ol>
    </div>
    
    <div class="container">
    
        {{-- Error / Info Messages --}}

        <h1>Create New</h1>
        
        @if ($message != '')
            <br><div class="well">{!! $message !!}</div>
        @endif
                
        @if ($mode == '')
            <form method="post" class="">
                <p>
                    <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                    <label for="url" class="control-label">URL</label>
                    <input class="form-control" name="url" value="{{{ $url }}}" type="text" maxlength="255">
                </p>
                <p>
                    <label><input type="checkbox" name="ignoreExistingDomain" value="1"> Allow URL to be added even if there is an existing URL on the same website domain.</label>
                </p>
                <p>
                    <button class="btn btn-primary submit" name="mode" value="insert" type="submit">Create</button>
                </p>
            </form>
        
        @elseif ($mode == 'done') 
            
            <div class="alert alert-success">
                <p><i class="fa fa-check-circle"></i>&nbsp; Link Created Successfully.</p>
                <p><a href="@routeURL('staff-incomingLinks', $incomingLink->id)">Edit</a></p>
            </div>
            
        @endif 
        
    </div>

@stop
