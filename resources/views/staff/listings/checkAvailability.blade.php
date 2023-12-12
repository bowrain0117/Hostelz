@extends('layouts/admin')

@section('title', 'Team Menu - Hostelz.com')


@section('header')
    @livewireStyles
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Listing', route('staff-listings', $listing->id)) !!}
            {!! breadcrumb('Check Availability') !!}
        </ol>
    </div>

    <div class="container">

        @if (!empty($message))
            <div class="well">{!! $message !!}</div>
        @endif

        <h2>Check Availability for "{{ $listing->name }}"</h2>
            <livewire:staff.listing.check-availability :listing="$listing"/>

    </div>

@stop

@section('pageBottom')
    @livewireScripts
@stop
