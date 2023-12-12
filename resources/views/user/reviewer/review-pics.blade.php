<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Review Photos - Hostelz.com')

@section('header')
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('My Reviews', routeURL('reviewer:reviews')) !!}
            {!! breadcrumb('Review', routeURL('reviewer:reviews', $review->id)) !!}
            {!! breadcrumb('Review Photos') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Review') !!}</div>

        <h1 class="hero-heading h2">"{{{ $review->listing->name }}}" Review</h1>
            
        <h2>Upload Photos</h2>
        <ul>
            <li>The photos should be ordered in the order of what someone going to stay at the hostel would see.  So what that means is the first photo should be the one of the outside of the hostel building, then any reception and common area photos, then photos of the dorm room, and finally photos of the bathroom/showers if any.</li>
            <li>By uploading pictures you are declaring that you are the exclusive owner to all rights to these photos and you are agreeing to transfer those rights to Hostelz.com.</li>
        	<li>DO NOT UPLOAD ANY PHOTOS YOU DIDN'T TAKE YOURSELF.</li>
        </ul>
            
        <h3>Photos for this Review</h3>
        
        @include('Lib/fileListHandler', [ 'fileListMode' => 'photos' ])
                
        <br>
        <h3>Upload New Photos</h3>
                             
        @include('Lib/fileUploadHandler')
        
        @if ($review->listing->cityInfo)
            <br><br><br>
            <div class="well">
                We also appreciate any photos you want to submit of the hostel's city! 
                <b>To upload city photos of {!! $review->listing->city !!}, click <a href="{!! routeURL('submitCityPics', $review->listing->cityInfo->id) !!}">here</a></b>.
            </div>
        @endif
    
    </div>

@stop

@section('pageBottom')

    
    @parent

@endsection
