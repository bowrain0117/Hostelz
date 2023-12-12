<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts.admin')

@section('title', 'Edit District')

@section('header')
    @livewireStyles
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Districts', routeURL('staff:district:index')) !!}
            @isset($slp->subjectable)
                {!! breadcrumb($slp->subjectable->city) !!}
            @endisset
        </ol>
    </div>

    <div class="container">

        <livewire:staff.district.edit :district="$district"/>

    </div>

@stop

@section('pageBottom')
    @livewireScripts
@stop
