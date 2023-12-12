@extends('layouts/admin')

@section('title', 'Add Image')

@section('header')
    @parent
@stop

@section('content')


    <div class="pt-3 pb-5 container">
        <div class="breadcrumbs">
            <ol class="breadcrumb" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb('Hostels Chains', routeURL('staff-hostelsChain')) !!}
                {!! breadcrumb($hostelChain->name, routeURL('staff-hostelsChain', $hostelChain->id)) !!}
                {!! breadcrumb('Add Image') !!}
            </ol>
        </div>

        @include('Lib/fileListHandler', [ 'fileListMode' => 'photos' ])

        <h3>Upload New Image</h3>

        @include('Lib/fileUploadHandler')


    </div>
@stop

@section('pageBottom')

@endsection