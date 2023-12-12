@extends('layouts/admin')

@section('title', 'Imports - Hostelz.com')

@section('header')
    @livewireStyles
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Imports') !!}
        </ol>
    </div>

    <div class="container">

        <h2>Imports</h2>

        <livewire:staff.import.imports/>

    </div>

@stop

@section('pageBottom')
    @livewireScripts

    <script src="{{ mix('js/app.js')}}"></script>
@stop
