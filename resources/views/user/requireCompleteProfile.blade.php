<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Complete Profile Required'))

@section('content')


<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Complete Profile Required') !!}
        </ol>
    </div>
    @if (!auth()->user()->profilePhoto)
        <div class="alert alert-danger">Please upload first your profile photo. Return to the user menu and visit "@langGet('UserSettingsHandler.actions.profilePhoto')".</div>
    @endif
        
	@if (auth()->user()->name == '' && auth()->user()->nickname == '')
        <div class="alert alert-danger">Please complete your profile first by entering your name and nickname. Return to the user menu and visit "@langGet('UserSettingsHandler.actions.settings')".</div>
	@endif
</div>

@stop