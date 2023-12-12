<?php

Lib\HttpAsset::requireAsset('booking-main.js');

?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', 'Hostelling International-USA (HI-USA)')

@section('content')
    <section class="pt-3 pb-5 container">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumbs -->
                <ul class="breadcrumb black text-dark px-0 mx-sm-n3 mx-lg-0">
                    {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                    {!! breadcrumb('Hostelling International-USA (HI-USA)') !!}
                </ul>

                <h1 class="hero-heading">HI USA</h1>

                <h2>Hostelling International-USA is a nonprofit organization that provides you a wide range of benefits, including:</h2>

                <ul>
                    <li> Discounted access to more than 4,000 hostels in over 60 countries worldwide.
                    <li> Discounted access to over 100 hostel locations in the United States.
                    <li> A free handbook of all U.S. hostels, upon request.
                    <li> Worldwide discounts in restaurants, retail stores, on attractions and
                    more.
                    <li> A monthly online newsletter, HI-USA Travel Bytes, emphasizing travel tips.
                    <li> Savings up to 85% on international phone calls, free e-mail access,
                    voicemail and travel information through eKit.
                    <li> Discounts on Alamo and Hertz car rentals.
                    <li> Discounts on Greyhound Travel.
                    <li> Access to programs and activities at local hostels and councils (local
                    chapters).
                    <li> Access to making prepaid reservations at hostel locations worldwide.
                    <li> Worldwide access to commission-free currency exchange.
                    <li> Free seminars on traveling abroad.
                    <li> Free basic international travel insurance coverage and discounts on optional upgrades.
                    <li> FreeNites & More is the loyalty program introduced by Hostelling International to award its members with valuable extras with their membership.
                </ul>

                <p class="text-center"><img src="{!! routeURL('images', 'hiBigLogo.gif') !!}" alt="Hostelling International-USA" title="Hostelling International-USA"></p>


                {{--
                <h2>The types and prices of each membership:</h2>
                <p>
                    <b>YOUTH: FREE</b> (for those under 18 years of age) <br>
                    <b>ADULT: $28 ANNUALLY</b> (18-54 years of age) <br>
                    <b>SENIOR: $18 ANNUALLY</b> (55+ years of age)<br>
                    <b>LIFE: $250</b> (one-time fee & open to all ages)
                </p>

                <h3 class="text-center"><a target="_blank" href="https://www.hiayh.net/hiusassa/memssaord.wizstep1" style="text-decoration: underline">Order your HI-USA membership card...</a></h3>
                --}}

            </div>
        </div>
    </section
@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop
