@extends('layouts/admin')

@section('header')
    
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Sync') !!}
        </ol>
    </div>
    
    <div class="container-fluid">

        <h1>Sync</h1>
        
        @include('Lib/devSync')

    </div>

@stop
