@extends('layouts/admin')

@section('title', 'Imported Import')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Imported Import') !!}</li>
        </ol>
    </div>
    
    <div class="container">
    
        <h1>Import</h1>
        
        <p>Note: Booking.com requires that we do imports between 5pm - 2am central time.</p>
        
        @if (!Request::input('doIt'))
            <h3 class="text-warning">This is a Test Run</h3>
            <form method="post">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                <button class="btn btn-primary" type="submit" name="doIt" value="true">Do Actual Import</button>
            </form>
            <br>
        @endif
        
        <pre>{!! $output !!}</pre>
        
    </div>

@stop
