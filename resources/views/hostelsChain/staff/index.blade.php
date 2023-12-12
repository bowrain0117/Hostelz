@extends('staff/edit-layout')

<?php

$hostelChain = $formHandler->model;

?>

@section('aboveForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="{!! $hostelChain->path !!}">View Hostel Chain</a></li>
            </ul>
        </div>

    @endif

@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <li class="list-group-item">
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-listings', [ 'hostels_chain_id' => $hostelChain->id ]) !!}" >
                    <i class="fa fa-search"></i>Listings <span class="pull-right">({{ $hostelChain->listingsCount }})</span>
                </a>
            </li>

            <li class="list-group-item">
                @if($hostelChain->imageThumbnails)
                    <img src="{{ $hostelChain->imageThumbnails }}" width="30" alt="">
                @else
                    <i class="fa fa-picture-o" aria-hidden="true"></i>
                @endif
                <a href="@routeURL('staff-hostelsChain:imageCreate', $hostelChain->slug)">Image</a>
            </li>


        </div>

    @endif

@stop


@section('belowForm')


@stop


@section('pageBottom')

{{--    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])--}}

    @parent

@endsection

