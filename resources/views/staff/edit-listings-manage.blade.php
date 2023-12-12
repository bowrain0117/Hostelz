@extends('layouts/admin')

@section('title', 'Manage Listing - ' . langGet('ListingEditHandler.actions.'.$listingEditHandler->action))

@section('header')
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            @if ($listingEditHandler->listing)
                {!! breadcrumb('Listing', routeURL('staff-listings', [ $listingEditHandler->listing->id ])) !!}
            @endif
            {!! breadcrumb(langGet('ListingEditHandler.actions.'.$listingEditHandler->action)) !!}
        </ol>
    </div>
    
    <div class="container">
        @include('partials/listingEditHandler')
    </div>

@stop

@section('pageBottom')
    @parent
@endsection
