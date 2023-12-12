@extends('layouts/admin')

@section('title', 'User Settings - ' . langGet('UserSettingsHandler.actions.'.$userSettingsHandler->action))

@section('header')
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            @if ($userSettingsHandler->user)
                {!! breadcrumb('User', routeURL('staff-users', [ $userSettingsHandler->user->id ])) !!}
            @endif
            {!! breadcrumb(langGet('UserSettingsHandler.actions.'.$userSettingsHandler->action)) !!}
        </ol>
    </div>
    
    <div class="container">
    
        @include('partials/userSettingsHandler')
        
    </div>

@stop

@section('pageBottom')

    
@endsection
