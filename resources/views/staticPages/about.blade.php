<?php
Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.AboutMetaTitle', ['year' => date("Y")] ))

@section('header')
    <meta name="description" content="{!! langGet('SeoInfo.AboutMetaDescription', ['year' => date("Y")]) !!}">
@stop

@section('content')
    <section>
        <div class="container">
            <div class="col-12 mb-lg-6 mb-6 px-0">

                <!--  Breadcrumbs  -->
                <ul class="breadcrumb black px-0 mx-lg-0">
                    {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                    {!! breadcrumb(langGet('global.AboutHostelz')) !!}
                </ul>
                <h1 class="mb-3 mb-lg-5 pb-md-2">@langGet('global.AboutHostelz')</h1>

                <!-- Open text -->
                <div class="mb-6">
                    <h2 class="mb-3">@langGet('about.WhyCreateSite.title')</h2>
                    <p class="mb-3">@langGet('about.WhyCreateSite.desc', ['href' => routeURL('articles', 'find-the-best-hostels')])</p>
                    <p class="mb-3 mb-md-4">@langGet('about.WhyCreateSite.p1')</p>
                    <p class="mb-3 mb-md-4">@langGet('about.WhyCreateSite.p2')</p>
                    <p class="mb-3">@langGet('about.WhyCreateSite.p3')</p>
                    <p class="mb-3">@langGet('about.WhyCreateSite.p4')</p>
                </div>

                <!-- Text + List -->
                <div class="mb-6">
                    <h2 class="mb-3">@langGet('about.DifferentThanOther.title')</h2>
                    <p class="mb-3 mb-md-4">@langGet('about.DifferentThanOther.p-1')</p>
                    <ul class="mb-2">
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-1')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-2')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-3')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-4')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-5')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-6')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-7')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-8')</li>
                        <li class="mb-4">@langGet('about.DifferentThanOther.li-9')</li>
                    </ul>
                    <p class="mb-2 pt-2">@langGet('about.DifferentThanOther.p-2')</p>
                </div>

                <!-- Text + Img -->
                <div class="mb-6">
                    <h2 class="mb-3">@langGet('about.HowCanISave.title')</h2>
                    <p class="mb-2">@langGet('about.HowCanISave.subtitle', ['href' => routeURL('articles', 'cheapest-hostel-booking-website')])</p>
                    <div class="d-flex my-4">
                        <img src="/pics/articles/originals/57/1778257.png" alt="#" class="mw-100 m-auto">
                    </div>
                </div>

                <!-- Text -->
                <div class="mb-6">
                    <h2 class="mb-3">@langGet('about.WhyStayInHostel.title')</h2>
                    <p class="mb-3 mb-md-4">@langGet('about.WhyStayInHostel.p-1')</p>
                    <p class="mb-3">@langGet('about.WhyStayInHostel.p-2')</p>
                    <p class="mb-3">@langGet('about.WhyStayInHostel.p-3')</p>
                    <p class="mb-3">@langGet('about.WhyStayInHostel.p-4')</p>
                    <p class="mb-3">@langGet('about.WhyStayInHostel.p-5', ['href' => routeURL('articles', 'staying-in-hostels-tips')])</p>
                    <p class="mb-3">@langGet('about.WhyStayInHostel.p-6')</p>
                </div>

                <!-- Text + Social -->
                <div class="mb-6">
                    <h2 class="mb-3">@langGet('about.Help.title')</h2>
                    <p class="mb-3 mb-md-4">@langGet('about.Help.p-1')</p>
                    <p class="mb-3 mb-md-4">@langGet('about.Help.p-2')</p>
                    <p class="mb-3 mb-md-4">@langGet('about.Help.p-3')</p>

                    <div class="row justify-content-around mb-3">
                        <ul class="list-inline mb-0 mt-2 mt-md-0">
                            <li class="list-inline-item"><a href="https://www.facebook.com/hostelz" target="_blank"
                                                            title="Facebook Hostelz" class="mr-3"><i
                                            class="fab fa-facebook text-hover-primary h1"></i></a></li>
                            <li class="list-inline-item"><a href="https://www.instagram.com/hostelz/" target="_blank"
                                                            title="Instagram Hostelz" class="mr-3"><i
                                            class="fab fa-instagram text-hover-primary  h1"></i></a></li>
                            <li class="list-inline-item"><a href="https://youtube.com/@Hostelz" target="_blank"
                                                            title="Youtube Hostelz" class="mr-3"><i
                                            class="fab fa-youtube text-hover-primary  h1"></i></a></li>
                            <li class="list-inline-item"><a href="https://pinterest.com/hostelz/" target="_blank"
                                                            title="Pinterest Hostelz" class="mr-3"><i
                                            class="fab fa-pinterest text-hover-primary  h1"></i></a></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Bottom Navigation-->
    <section class="my-5 my-lg-7">
        <div class="container">
            <div class="row">
                <div class="offset-lg-1 col-lg-5 flex-center--column mb-4 mb-sm-5 mb-lg-0">
                    <p class="h4 text-center font-weight-bolder mb-4 ie-w-100">@langGet('about.BottomNav.right.title')</p>
                    <a href="{!! routeURL('articles', 'hostel-owner-suggestions') !!}"
                       class="btn btn-lg btn-outline-primary bg-primary-light mt-4 tt-n py-2 px-sm-5 font-weight-600 rounded"> @langGet('about.BottomNav.right.link')</a>
                </div>
                <div class="col-lg-5 flex-center--column">
                    <p class="h4 text-center font-weight-bolder mb-4">@langGet('about.BottomNav.left.title')</p>
                    <a href="{!! routeURL('articles', 'what-to-pack') !!}"
                       class="btn btn-lg btn-outline-primary bg-primary-light mt-4 tt-n py-2 px-sm-5 font-weight-600 rounded">@langGet('about.BottomNav.left.link')</a>
                </div>
            </div>
        </div>
    </section>
@stop

@section('pageBottom')
    @parent

    <script>
        initializeTopHeaderSearch();
    </script>
@stop