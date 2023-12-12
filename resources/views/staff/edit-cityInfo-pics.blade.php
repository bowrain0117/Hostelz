<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts/admin')

@section('title', 'City Pics')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('City', routeURL('staff-cityInfos', $cityInfo->id)) !!}
            {!! breadcrumb($cityInfo->city . ' City Pics') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <h1>{{{ $cityInfo->city }}} City Pics</h1>
        
        @include('Lib/fileListHandler', [ 'fileListMode' => 'photos', 'fileListShowStatus' => true ])
    
        <br>
        
        <h3>Upload New Photos</h3>
        
        @include('Lib/fileUploadHandler')
        
    </div>

@stop
