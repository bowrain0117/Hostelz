<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('UserSettingsHandler.actions.'.$userSettingsHandler->action))

@section('header')
    @parent
@stop

@section('content')

@include('user.navbarDashboard')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            @if (!in_array($userSettingsHandler->action, \App\Helpers\UserSettingsHandler::$nonLoggedInActions))
            	{!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            @endif
            {!! breadcrumb(langGet('UserSettingsHandler.actions.'.$userSettingsHandler->action)) !!}
        </ol>
    </div>
	@include('partials/userSettingsHandler')
</div>
@stop

@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'dreamDestinations', 'placeholderText' => "Search by City", 'allowClear' => false, 'selectSelector' => '.dreamDestinations', 'initSelection' => true ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'favoriteHostels', 'placeholderText' => "Search by listing ID, name, or city.", 'allowClear' => false, 'selectSelector' => '.favoriteHostels', 'initSelection' => true ])

    @parent

@endsection
