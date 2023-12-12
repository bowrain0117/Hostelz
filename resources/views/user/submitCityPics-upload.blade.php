<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', langGet('User.menu.SubmitCityPics').' - Hostelz.com')

@section('header')
    @parent
@stop

@section('content')

@include('user.navbarDashboard')

<div class="pt-3 pb-5 container">
    
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb(langGet('User.menu.SubmitCityPics'), routeURL('submitCityPicsFindCity')) !!}
            {!! breadcrumb($cityInfo->city) !!}
        </ol>
        <h1 class="hero-heading h2">{!! langGet('Staff.icons.Pic') !!} {!! $cityInfo->city !!}, {!! $cityInfo->country !!} Photos</h1>
			        
        <h3 class="mt-3">Your Photos for this City</h3>
        
        <p>You can use this page to submit photos of {!! $cityInfo->city !!}. {{-- (not yet paying) We will use the photo on the city page after your photo(s) are approved. You can upload as many photos per city as you would like (but we pay you per city, not per photo). --}}
        </p>
        <ul>
            <li>By uploading pictures you are declaring that you are the exclusive owner to all rights to these photos and you are agreeing to share those rights with Hostelz.com.</li> 
            <li><b>ONLY UPLOAD PHOTOS YOU TOOK TOOK YOURSELF</b>. We also prefer photos that haven't already been posted online on other websites.</li>
        </ul>
        
        <h3 class="mt-3">Photos</h3>
        
        @if (!$approvedFileList->list->isEmpty())
            <h4 class="mt-3">Approved Photos</h4>
            @include('Lib/fileListHandler', [ 'fileListMode' => 'photos', 'fileList' => $approvedFileList ])
            <h4 class="mt-3">New Photos</h4>
        @endif
        
        @include('Lib/fileListHandler', [ 'fileListMode' => 'photos', 'fileList' => $fileList ])
         
        <h3 class="mt-3">Upload New Photos</h3>
                             
        @include('Lib/fileUploadHandler')
    </div>
</div>
@stop