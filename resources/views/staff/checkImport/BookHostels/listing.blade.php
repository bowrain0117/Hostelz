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

                @isset($result['facilities'])
                    <div class="col-md-12 mb-4">
                        <h4>API FACILITIES</h4>
                        <ul class="list-group" style="display: grid; grid-template-columns: repeat(3, 1fr);">
                            @foreach($result['facilities'] as $item)
                                <li class="list-group-item">
                                    <span class="">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endisset

                @if (!empty($hostelzFeatures))
                    <div class="col-md-12 mb-4">
                        <h4>Hostelz Features</h4>
                        @foreach($hostelzFeatures as $key => $item)
                            <li class="list-group-item">
                                <span class="text-info">{{ $key }}</span> -
                                <span class="">{{ is_array($item) ? implode(', ', $item) : $item }}</span>
                            </li>
                        @endforeach
                    </div>
                @endif

                <div class="col-md-12">
                    <h4>Reviews RAW DATA </h4>
                    <?php dump($reviews); ?>
                </div>

                <div class="col-md-12">
                    <h4>RAW DATA</h4>
                    <?php dump($result); ?>
                </div>

            </div>
        </div>
    </div>

@stop
