<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true])

@section('title', 'Your Dashboard Hostelz.com')

@section('content')
    @include('user.navbarDashboard')

    @include('user.public.preview')

    <div class="container pb-5 pt-3">
        <div class="breadcrumbs">
            <ol class="breadcrumb black" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('User.menu.UserMenu')) !!}
            </ol>
        </div>

        @if ($message != '')
            <div class="well">{!! $message !!}</div>
        @endif

        @if (in_array(false, $userSettingsValidations))
        <section class="py-5 py-lg-6 ">
            <div class="container">
                <div class="text-center">
                    <p class="subtitle mb-2">Welcome to Hostelz</p>
                    <h3 class="title-2 cl-dark text-left text-lg-center mb-5">Let's complete your profile together!</h3>
                </div>
                <div class="row justify-content-around">
                    @foreach ($userSettingsValidations as $pageType => $validationStatus)
                        @if (!$validationStatus)
                            <div class="col-md-6 col-lg-4 mb-4 mb-lg-0">
                                <div class="card rounded-lg shadow-1 border-0 py-4 px-3 mx-lg-4 position-relative">
                                    <div class="text">
                                        <a class="" href="{!! routeURL('user:settings', [$pageType]) !!}">
                                            <h3 class="text-center">{!! langGet('UserSettingsHandler.icons.' . $pageType) !!}<span class="ml-1">{!! langGet('UserSettingsHandler.actions.' . $pageType) !!}</span></h3>
                                        </a>

                                        <p class="testimonial-text">{!! langGet('UserSettingsHandler.actionsComplete.' . $pageType) !!}</p>
                                        <div class="text-center"><a class="" href="{!! routeURL('user:settings', [$pageType]) !!}"><button class="btn btn-sm btn-primary rounded px-4 px-sm-5">Get started</button></a></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <h1 class="h2">
            @lang('User.menu.welcome'){{ $user->name ? ', ' . $user->name : ($user->nickname ? ', ' . $user->nickname : '') }}
        </h1>

        <div class="row well py-3">
            <div class="col-lg-4 mb-4">
                <h3>{!! langGet('User.menu.UserMenu') !!}</h3>
                <ul class="list-group">

                    <li class="list-group-item mb-2">
                        <a class="" href="{!! routeURL('user:reservations') !!}">
                            <i class="fas fa-bed"></i><span class="ml-2">Your Reservationz</span>
                        </a>
                    </li>

                    @foreach ($userSettingsValidations as $pageType => $validationStatus)
                        <li class="list-group-item mb-2">
                            <a class="" href="{!! routeURL('user:settings', [$pageType]) !!}">
                                {!! langGet('UserSettingsHandler.icons.' . $pageType) !!}<span class="ml-2">{!! langGet('UserSettingsHandler.actions.' . $pageType) !!}</span>
                            </a>
                            @if (!$validationStatus)
                                <span class="text-danger validationWarning"><span class="badge badge-info ml-1">@langGet('mgmt.pleaseComplete')</span></span>
                            @endif
                        </li>
                    @endforeach

                    <li class="list-group-item mb-2">
                        <a class="" href="{!! routeURL('articles', 'best-hostel-tips-backpacking') !!}">
                            <i class="fas fa-gem"></i><span class="ml-2">@langGet('global.ExclusiveContent') <span
                                    class="badge badge-primary ml-1">Pluz</span></span>
                        </a>
                    </li>

                    <li class="list-group-item mb-2">
                        <a class="" href="{!! routeURL('wishlist:index') !!}">
                            <i class="fa fa-heart" aria-hidden="true"></i><span class="ml-2">Your Wishlists</span>
                        </a>
                    </li>

                    <li class="list-group-item d-md-none mb-2">
                        <a class="" href="{!! routeURL('logout') !!}">
                            <i class="fa fa-sign-out-alt" aria-hidden="true"></i><span
                                class="ml-2">@langGet('global.logout')</span>
                        </a>
                    </li>
                </ul>
            </div>

            @if (auth()->user()->hasAnyPermissionOf(['reviewer', 'staffWriter', 'placeDescriptionWriter']))
                <div class="col-lg-4 mb-4">
                    <h3>{!! langGet('User.menu.TravelWriting') !!}</h3>
                    @if (auth()->user()->hasPermission('reviewer'))
                        <ul class="list-group">
                            <li class="list-group-item mb-2"><a class=""
                                    href="{!! routeURL('reviewer:reviews') !!}">@langGet('Staff.icons.Review')<span class="ml-2">@langGet('User.menu.HostelReviews')</span></a></li>
                            {{-- 'staffWriter' -> TravelArticles else  <div>If you're also interested in getting paid to write <b>travel articles</b> for Hostelz.com, <a href="">sign-up here</a>.</div> --}}
                        </ul>
                    @endif
                    {{--
                    @if (auth()->user()->hasPermission('placeDescriptionWriter'))
                        <ul class="list-group">
                            <li class="list-group-item mb-2"><a class="" href="{!! routeURL('placeDescriptions') !!}">@langGet('Staff.icons.CityInfo')@langGet('User.menu.PlaceDescriptions')</a></li>
                        </ul>
                    @endif
                    --}}
                    <ul class="list-group">
                        <li class="list-group-item mb-2"><a class="" href="{!! routeURL('submitCityPicsFindCity') !!}">@langGet('Staff.icons.Pic')<span class="ml-2">@langGet('User.menu.SubmitCityPics')</span></a></li>
                    </ul>
                </div>

                {{--
                <div class=""><strong>Hostelz.com is Hiring Marketers</strong> - We're looking to hire a few people to work from home contacting other websites by email to let them know about Hostelz.com and to ask them to link to us.  <a href="http://waco.craigslist.org/mar/5556232647.html">Details here.</a> </div>
                --}}

                @if (!auth()->user()->hasAnyPermissionOf(['staff', 'affiliate']))
                    <div class="col-lg-4 mb-4">
                        <h3>Become an Affiliate</h3>
                        <p><b>Do you have a blog or website? Join the Hostelz.com Affiliate Program.</b> Add a link to
                            Hostelz.com from your website and earn a commission each time someone follows the link and
                            makes a booking. {{-- <a href="@routeURL('affiliateSignup')">Sign-up for more info.</a> --}}
                        </p>
                        <p>The programme is currently on hold. Please contact us for more information and to get on the
                            waiting list.</p>
                    </div>
                @endif

            @endif

            @if (auth()->user()->hasAnyPermissionOf(['staff', 'affiliate']) || auth()->user()->mgmtListings)
                <div class="col-lg-4 mb-4">
                    <h3>{!! langGet('User.menu.SpecialAccess') !!}</h3>
                    <p class="mb-2">{!! langGet('User.menu.YourSpecialAccess') !!}</p>
                    <ul class="list-group">
                        @if (auth()->user()->hasPermission('staff'))
                            <li class="list-group-item mb-2"><a class="" href="{!! routeURL('staff-menu') !!}"><strong>Staff
                                        Menu</strong> &raquo;</a></li>
                        @endif
                        @if (auth()->user()->mgmtListings)
                            <li class="list-group-item mb-2"><a class=""
                                    href="{!! routeURL('mgmt:menu') !!}"><strong>{!! langGet('User.menu.ListingManagement') !!}</strong>
                                    &raquo;</a></li>
                        @endif

                        @if (auth()->user()->hasPermission('affiliate'))
                            <li class="list-group-item mb-2"><a class=""
                                    href="{!! routeURL('affiliate:menu') !!}"><strong>{!! langGet('User.menu.AffiliateProgram') !!}</strong>
                                    &raquo;</a></li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
@stop
