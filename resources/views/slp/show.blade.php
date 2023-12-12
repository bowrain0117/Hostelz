<?php
Lib\HttpAsset::requireAsset('wishlistMain.js');
Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true, 'notIncludeMainJs' => true ])

@section('headerJsonSchema')
    {!! $schema->toScript() !!}
@stop

@section('title', $slp->meta->meta_title)

@section('header')
    <meta name="description" content="{{ $slp->meta->meta_description }}">
@stop

@section('content')

    <section class="hero text-white dark-overlay bg-cover hero-blog flex-center mb-3 mb-lg-5">
        <div class="dark-overlay hero-blog flex-center w-100">

            @if($slp->mainPic)
                <picture>
                    <source srcset="{{ $slp->mainPic->src['big_webp'] }}" type="image/webp">
                    <img
                            class="bg-image"
                            src="{{ $slp->mainPic->src['big'] }}"
                            alt="{{ $slp->mainPic->title }}"
                    >
                </picture>
            @endif

            <div class="card-img-overlay d-flex align-items-center">
                <div class="w-100 overlay-content container flex-center--column">
                    <ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0 text-white" vocab="http://schema.org/"
                        typeof="BreadcrumbList">
                        {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                        {!! breadcrumb($slp->category->title(), route('slp.index.' . $slp->category->value)) !!}
                        {!! breadcrumb($slp->subjectable->city) !!}
                    </ul>

                    <h1 class="text-center mb-0 h2 text-white">
                        {{ ($slp->meta->title) }}
                    </h1>

                    <p class="py-2 mb-1 text-center article-author-info">
                        <span class="d-inline-flex align-middle mr-2">
                            <img src="
                                @if ($slp->author->profilePhoto)
                                    {!! $slp->author->profilePhoto->url([ 'thumbnails' ]) !!}
                                @else
                                    {!! routeURL('images', 'hostelz-blogger-writer.jpg') !!}
                               @endif
                                " alt="" class="avatar avatar-lg p-1"
                            >
                            @if($slp->author->isAdmin())
                                <span style="margin-left: -15px; z-index: 1;">
                                    @include('partials.svg-icon', ['svg_id' => 'verified-user-hostelz-white', 'svg_w' => '24', 'svg_h' => '24'])
                                </span>
                            @endif
                        </span>
                        Written by
                        <x-user-page-link :user="$slp->author"/>
                        <span class="mx-1">|</span>
                        Last update: {{ $slp->updated_at->format('M j, Y') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="container">
        <div class="row flex-column flex-lg-row">
            <div class="col-12 col-lg-8 pb-5 mb-5 slp-wrap">

                <x-slp::table-content/>

                <x-slp.content :$slp/>

                <x-slp::all-hostels-in-city :city="$slp->subjectable"/>

                <x-district.link-list :city="$slp->subjectable"/>

                <x-slp.more-guides :$slp/>

                <x-author-box :user="$slp->author"/>

            </div>

            <x-slp.sidebar :$slp/>

        </div>
    </section>

@stop


@section('pageBottom')

    @parent

    @include('wishlist.modalWishlists')
    @include('wishlist.modalCreateWishlist')
    @include('wishlist.modalLogin')
    @include('wishlist.toasts')

    <script>
        initializeTopHeaderSearch();
    </script>

    <script type="text/javascript">
        $(document).on("hostelz:frontUserData", function (e, data) {
            data.editURLFor = {target: 'slp', id: {{ $slp->id }}};
            return data;
        })

        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (data.editURL) {
                addEditURL(data.editURL)
            }
        });

        function addEditURL(editURL) {
            $('.edit-link').remove();

            $('.hero h1').after('<a class="text-white d-block text-center text-decoration-underline edit-link" href="' + editURL + '">edit link</a>');
        }

    </script>

    <script src="{{ mix('js/slp.js')}}"></script>
@stop
