<?php
    Lib\HttpAsset::requireAsset('wishlistIndexPage.js');
    Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', @langGet('wishlist.Wishlists').' - Hostelz.com')

@section('header')
    <meta name="description" content="">
    @parent
    <meta name="robots" content="noindex, nofollow">
@stop

@section('content')

@include('user.navbarDashboard')

<div class="pt-3 pb-5 container">

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb(langGet('wishlist.Wishlists' )) !!}
        </ol>
    </div>
    <h1 class="hero-heading h2">@langGet('wishlist.Wishlists')</h1>
    <div class="pb-5">
        <button id="createWishList" class="btn-lg btn-primary rounded px-4 px-sm-5" data-toggle="modal" data-target="#createWishlistModal">@langGet('wishlist.createList')</button>
    </div>

        <section class="pb-5">
            <div id="wishlistCreatePage">

            @if($lists->isNotEmpty())
                <div class="row">

                    @foreach($lists as $list)
                            <div class="col-xl-3 col-md-4 col-sm-6 mb-5">
                                <div class="card h-100 border-0 shadow">
                                    <a href="{!! $list->path !!}" title="{!! $list->name !!}" class="bg-gray-400 card-img-top">
                                        <div class="position-relative">
                                            
                                            <div class="listingCardSlider" style="min-height: 140px;" property="image" content="{!! $list->image !!}">
                                                <img src="{!! $list->image !!}" alt="" title="" style="object-fit: cover;" class="card-img-top">
                                            </div>
                                        </div>
                                    </a>
                                    {{-- div class="card-img-top overflow-hidden ">
                                        <a href="{!! $list->path !!}" class="d-flex align-items-center justify-content-center bg-gray-400"
                                           style="height: 180px;">

                                            @if ($list->image)
                                                <img src="{!! $list->image !!}" alt="{!! $list->name !!}" class="img-fluid w-100 h-100"/>
                                            @endif

                                        </a>
                                    </div> --}}
                                    <div class="card-body d-flex align-items-center">
                                        <div class="w-100">
                                            <h6 class="card-title"><a href="{!! $list->path !!}" class="text-decoration-none text-dark">{!! $list->name !!}</a></h6>
                                            <div class="d-flex card-subtitle">
                                                <p class="flex-grow-1 mb-0 text-sm">
                                                    @if($list->listingsCount)
                                                        {{ $list->listingsCount }} {{ trans_choice('wishlist.stays', $list->listingsCount) }}
                                                    @else
                                                        @langGet('wishlist.nothingSaved')
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    @endforeach

                </div>
            @else

                <p>@langGet('wishlist.noListings')</p>
                <p>Create your first Wishlist and start adding amazing hostels to it.</p>

            @endif

        </div>
        </section>
    </div>

@stop

@section('pageBottom')

    @parent

    @include('wishlist.modalCreateWishlist')

@stop