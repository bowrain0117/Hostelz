<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts/admin')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Data Correction') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <h1>Data Correction</h1>
        <h3>{{{ $actualDbTable }}} {{{ $actualDbField }}}</h3>
        
        @include('Lib/dataCorrectionHandler-mass')
        
        <br>
        <p>
            <a href="{!! routeURL('staff-dataCorrections') !!}/new?data[dbTable]={{{ $dbTable }}}&data[dbField]={{{ $dbField }}}">Create a New {{{ $dbField }}} Data Correction</a>
        </p>
            
    </div>

@stop

@section('pageBottom')
@endsection
