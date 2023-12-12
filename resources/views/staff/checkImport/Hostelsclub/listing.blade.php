<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts.admin')

@section('title', 'Check Import')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Back to Imported', routeURL('staff-importeds', [$imported->id])) !!}
        </ol>
    </div>

    <div class="container">

        {{-- Error / Info Messages --}}

        @if (!empty($message))
            <br><div class="well">{!! $message !!}</div>
        @endif

        <div class="staffForm">
            <div class="row">

                <div class="col-md-12">
                    <h4>RAW DATA</h4>
                    <?php dump($result); ?>
                </div>

            </div>
        </div>
    </div>

@stop
