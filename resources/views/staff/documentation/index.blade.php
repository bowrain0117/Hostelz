<?php

use App\Services\Payments;

?>

@extends('layouts/admin')

@section('title', 'Documentation - Hostelz.com')

@section('header')

    <style>

    </style>

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Documentation') !!}
        </ol>
    </div>

    <div class="container" style="margin-bottom: 16px">
        <a href="{{ route('documentation:edit') }}" class="btn btn-info">Edit</a>
    </div>

    @include('staff.documentation.content')

@stop
