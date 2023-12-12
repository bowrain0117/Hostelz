@extends('layouts/admin')

@section('title', 'Hostelz.com')

@section('content')
    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
        </ol>
    </div>
    <div class="container">
        <h1>Congratulations!</h1>
    </div>
@stop