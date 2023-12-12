<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?> 

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => false ])

@section('title', htmlentities($listingEditHandler->listing->name) . ' - ' . langGet('ListingEditHandler.actions.'.$listingEditHandler->action))

@section('header')
    @parent
@stop

@section('content')
<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Listing Management', routeURL('mgmt:menu')) !!}
            {!! breadcrumb(langGet('ListingEditHandler.actions.'.$listingEditHandler->action)) !!}
        </ol>
    </div>
    
    <div class=""> 
        @include('partials/listingEditHandler')
    </div>
</div>
@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop