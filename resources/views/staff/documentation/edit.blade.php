<?php

use App\Services\Payments;

Lib\HttpAsset::requireAsset('documentation.js');

?>

@extends('layouts/admin')

@section('title', 'Documentation - Hostelz.com')

@section('header')

    <style>

    </style>

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Documentation') !!}
        </ol>
    </div>

    <div class="container" style="margin-bottom: 16px">
        <a href="{{ route('documentation') }}" class="btn btn-info">View</a>
    </div>

    <div class="container">
        <form action="{{ route('documentation:update') }}" method="post">

            <input name="_method" type="hidden" value="PUT">

            {{ csrf_field() }}

            <div class="form-group text-right">
                <button class="btn btn-success" type="submit">Save</button>
            </div>

            <div class="form-group">
                <textarea class="wysiwyg" name="content" id="" cols="30" rows="50">
                    {!! $content !!}
                </textarea>
            </div>

            <div class="form-group text-right">
                <button class="btn btn-success" type="submit">Save</button>
            </div>

        </form>
    </div>

@stop
