<?php

Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet("loginAndSignup.paidReviewerSignup.title").' - Hostelz.com')

@section('header')
@stop

@section('content')
    <div class="pt-3 pb-5 container">
        <div class="breadcrumbs">
            <ol class="breadcrumb black" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet("loginAndSignup.paidReviewerSignup.title")) !!}
            </ol>
        </div>

        <h1 class="hero-heading h2">@langGet("loginAndSignup.paidReviewerSignup.title")</h1>

        <div class="alert alert-warning"><b>Due to a change in ownership of Hostelz.com in November 2019, we are
                currently no longer accepting new paid reviews at this time.</b></div>

        <p>Online since 2002, Hostelz.com is the largest hostel reviews and information source online. Along with guest
            ratings, we also pay travelers to write full reviews with photos to give our visitors as much information
            about the hostels as possible. Now you can help contribute to this important travel resource, and get paid
            for doing it.</p>
        <p>We pay <b>{!! \Lib\Currencies::format(\App\Models\Review::PAY_AMOUNT, 'USD', false) !!}</b> per review
            (that's roughly {!! \Lib\Currencies::convert(\App\Models\Review::PAY_AMOUNT, 'USD', 'EUR', true, false) !!}
            , {!! \Lib\Currencies::convert(\App\Models\Review::PAY_AMOUNT, 'USD', 'CAD', true, false) !!},
            or {!! \Lib\Currencies::convert(\App\Models\Review::PAY_AMOUNT, 'USD', 'AUD', true, false) !!}). Payments
            are made using PayPal (it's easy to sign up for a free account later if you don't already have one). A
            review is a few paragraphs (at least {!! \App\Models\Review::$minimumWordsAccepted !!} words) about the
            hostel and some photos taken with your digital camera. You can see an example of a review on Hostelz.com <a
                    href="{!! \App\Models\Listing\Listing::areLive()->findOrFail(215471)->getURL() !!}"
                    class="underline">here</a> (look for "The Hostelz.com Review").</p>
        <p>There is a minimum of two reviews per reviewer, but beyond that you can write as many as you like.</p>
        <h3>Requirements:</h3>
        <ul>
            <li>You must have good English grammar and writing skills.</li>
            <li>You must have experience with hostel stays</li>
        </ul>
        <p style="display: none;"><a href="@routeURL('paidReviewerSignup')" class="btn btn-info">Continue</a></p>
    </div>

@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop