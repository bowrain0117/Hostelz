<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', 'Upcoming Reservationz - Hostelz.com')

@section('header')
    <meta name="description" content="">
    @parent
    <meta name="robots" content="noindex, nofollow">
@stop

@section('content')

    @include('user.navbarDashboard')

    <section class="pt-3 pb-5 container">
        <div class="breadcrumbs">
            <ol class="breadcrumb black" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
                {!! breadcrumb('Reservationz') !!}
            </ol>
        </div>

        <x-user.reservations.reservations-list :$user/>

        <x-user.reservations.faq/>

    </section>

@stop

@section('pageBottom')

    @parent

@stop