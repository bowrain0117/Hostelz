<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts.admin')

@section('title', 'Edit Special Landing Pages')

@section('header')
    @livewireStyles
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Special Landing Pages', routeURL('slpStaff:index')) !!}
            @isset($slp->subjectable)
            {!! breadcrumb($slp->subjectable->city) !!}
            @endisset
        </ol>
    </div>

    <div class="container">

        <livewire:staff.slp.edit :slp="$slp"/>

    </div>

@stop

@section('pageBottom')
    @livewireScripts
@stop
