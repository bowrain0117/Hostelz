<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', replaceShortcodes(__('slp.categories.meta-title.'.$category->value)))

@section('header')
<meta name="description" content="{{ replaceShortcodes(__('slp.categories.meta-desc.'.$category->value)) }}">
@stop

@section('content')
<section class="hero text-white dark-overlay bg-cover hero-blog flex-center mb-3 mb-lg-5">
    <div class="dark-overlay hero-blog flex-center w-100">
        <picture>
            <source srcset="/images/slp-{{ $category->value }}.webp" type="image/webp">
            <img
                    class="bg-image"
                    src="/images/slp-{{ $category->value }}.jpg"
                    alt="{{ replaceShortcodes(__('slp.categories.title.'.$category->value)) }}"
                    title="{{ replaceShortcodes(__('slp.categories.title.'.$category->value)) }}"
                    loading="lazy"
            >
        </picture>

        <div class="card-img-overlay d-flex align-items-center">
            <div class="w-100 overlay-content container flex-center--column">
                <ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0 text-white" vocab="http://schema.org/" typeof="BreadcrumbList">
                    {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                    {!! breadcrumb(__('slp.categories.breadcrumb.'.$category->value)) !!}
                </ul>

                <h1 class="text-center mb-0 h2 text-white text-uppercase">
                    {{ replaceShortcodes(__('slp.categories.title.'.$category->value)) }}
                </h1>

            </div>
        </div>
    </div>
</section>

<section class="container">

    <x-slp.category :$category />

</section>
@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>

@stop
