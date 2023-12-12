<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => false, 'showHeaderSearch' => true ])


@section('title', 'Reviewer - Hostelz.com')

@section('content')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
        </ol>
    </div>
    
    <h1 class="hero-heading h2">Notice: Paid Reviews Program On Hold</h1>
    <div class="alert alert-warning">We are currently not accepting new paid reviews at this time.</div>

</div>

@stop 

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop