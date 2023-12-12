<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?> 

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => false ])

@section('title', 'Listing Management - Hostelz.com')

@section('header')
@stop 

@section('content') 

@include('user.navbarDashboard')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Listing Management') !!}
        </ol> 
    </div> 
    <h1 class="hero-heading h2">@langGet('mgmt.ListingManagement')</h1>
	
    <div class="row pb-5 d-flex justify-content-between">
        <div class="col-md-6">
            @forelse ($listings as $listing)
                <div class="pb-5">
                    <h2 class="hero-heading h3">Your Listing: <b>{{{ $listing['listing']->name }}}</b></h2>
                    <div class="@if ($listing['isLiveOrWhyNot'] == 'live')alert alert-success @else alert alert-warning @endif d-flex align-items-center flex-row">
                        <span>@langGet('mgmt.Status')</span>
                        @if ($listing['isLiveOrWhyNot'] == 'live')
                        <span class="text-success ml-1 font-weight-bold">{!! langGet('Listing.isLiveOrWhyNot.live') !!} <i class="fas fa-check"></i></span>
                            <a href="{!! $listing['listing']->getURL() !!}" target="_blank" class="btn btn-sm btn-light ml-auto">{!! langGet('mgmt.view') !!}</a>
                        @else            
                            <span class="text-warning ml-1 font-weight-bold">{!! langGet('Listing.isLiveOrWhyNot.'.$listing['isLiveOrWhyNot']) !!} <i class="fas fa-pause"></i></span>
                            <a href="{!! routeURL('mgmt-listing-manage', [$listing['listing']->id, 'preview' ]) !!}"  target="_blank" class="btn btn-sm btn-light ml-auto">{!! langGet('mgmt.preview') !!}</a>
                        @endif
                    </div>
                        
                    <ul class="list-group">
                        @foreach ($listing['validations'] as $pageType => $validationStatus)
                            <li class="list-group-item">
                                <a href="{!! routeURL('mgmt-listing-manage', [$listing['listing']->id, $pageType ]) !!}">
                                    {!! langGet('ListingEditHandler.icons.'.$pageType) !!} <span class="ml-1">{!! langGet('ListingEditHandler.actions.'.$pageType) !!}</span>
                                </a>
                                @if (!$validationStatus)
                                    <span class="validationWarning"><span class="badge badge-info ml-1">@langGet('mgmt.pleaseComplete')</span></span>
                                @endif
                                
                            </li>
                        @endforeach
                           
                            <li class="list-group-item">
                                <a href="{!! routeURL('mgmt-feature-listing', $listing['listing']->id) !!}">
                                    <i class="fa fa-certificate"></i> <span class="ml-1">Featured Listing Status:</span>
                                    @if ($listing['listing']->isFeaturedListing()) 
                                        <strong><span class="text-success">@langGet('ListingSubscription.status.active')</span></strong>
                                    @else
                                        <span class="text-danger">Not Featured</span>
                                    @endif
                                </a>
                                
                                @if ( $listing['hasNotified'] )
                                    <span class="badge badge-success ml-1">We keep you posted! <i class="fas fa-check"></i></span>
                                @elseif (! $listing['listing']->isFeaturedListing() && !$listing['hasNotified']) 
                                    <span class="validationWarning"> <span class="badge badge-primary ml-1">Coming soon</span></span>
                                @endif

                            </li>                
                        </ul>
                        
                        @if (!$listing['otherMmgmtUsers']->isEmpty())
                            <div class="panel-footer my-4 text-xs">
                                @langGet('mgmt.otherMgmtUsers')
                                @foreach ($listing['otherMmgmtUsers'] as $otherUser)
                                    <em>{{{ $otherUser->username }}}</em>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty 
                    <div class="well">Your account doesn't currently have any listings.</div>          
                @endforelse 
            </div>
                                 
                <div class="col-md-6 pb-5">
                    <h2 class="hero-heading h3">@langGet('mgmt.Information')</h2>
                    {{-- Card 1 --}}
                    <div class="card border-0 shadow mb-5">
                        <div class="card-header py-4 border-0">
                            <div class="media align-items-center">
                                <div class="media-body">
                                    <p class="subtitle text-sm text-primary">$50 Voucher for you + Free Trial</p>
                                    <h4 class="mb-0">Save $50 on Top Hostel Management Software</h4>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" aria-labelledby="title" aria-describedby="desc" role="img" xmlns:xlink="http://www.w3.org/1999/xlink" style="width: 50px;height: 50px;">
                                    <title>Money Box</title>
                                    <desc>A line styled icon from Orion Icon Library.</desc>
                                    <path data-name="layer1" d="M28.8 19.5a21.1 21.1 0 0 0-7.1 2.2c-7.9 4.2-9.2 11-9.2 16.3s2.3 13.1 10.8 20h7.3v-4l8.9.5a20.1 20.1 0 0 0 2.3 3.5h6.7v-6a47.9 47.9 0 0 0 6-5c2.9.3 5.9-2 7-8.7 0-1-.5-1.3-1-1.3a6.8 6.8 0 0 1-3-1c-.4-.5-1.6-4.7-3.8-8a11.7 11.7 0 0 1 4.1-5.2c-2.8-1.4-5.7-2.4-11.4-1.8-1.5-.3-4-.9-6.4-1.3" fill="none" stroke="#202020" stroke-miterlimit="10" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>
                                    <circle data-name="layer2" cx="34.5" cy="14" r="8" fill="none" stroke="#202020" stroke-miterlimit="10" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></circle>
                                    <path data-name="layer1" d="M12.5 38c-2.9-.3-10-1.4-10 2s1.9 3.4 3.8 2.6 4.3-5.5-3.8-8.6m25.7-8.9a20.1 20.1 0 0 1 12.7-.1" fill="none" stroke="#202020" stroke-miterlimit="10" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>
                                    <circle data-name="layer1" cx="48.5" cy="34" r="1" fill="none" stroke="#202020" stroke-miterlimit="10" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></circle>
                                </svg>
                            </div>
                        </div>
                        <div class="pb-5 card-body">
                            <p class="font-weight-bold"><a href="https://www.hostelz.com/cloudbeds" target="_blank" title="cloudbeds">Cloudbeds</a> is one of the most popular Software to use for Hostel Management!</p>

                            <p>It is an all-in-one property management software. Instead of managing different aspects of your business separately, you can easily manage your property with a single tool. Reduce human errors drastically, say good-bye to overbookings and save time.</p>
                            <p>Cloudbeds Software include:</p>
                            <ul>
                                <li>Property Management System</li>
                                <li>Booking Engine</li>
                                <li>Channel Manager</li>
                                <li>Revenue Management</li>
                                <li>Marketplace</li>
                                <li>Finance &amp; Payments</li>
                            </ul>
                            <p><a target="_blank" class="btn bg-primary text-white d-flex mb-3 align-items-center justify-content-center" title="Cloudbeds" href="https://www.hostelz.com/cloudbeds"><span class="overflow ml-2 d-inline-block">Get your $50 Voucher here</span></a></p>
                            <p class="text-center">In partnership with <img src="https://ambassador-api.s3.amazonaws.com/uploads/marketing/12193/2019_05_24_13_17_49.png" alt="Cloudbeds" style="width: 140px;" border="0"></p>
                        </div>
                    </div>
                    {{-- Card 2 --}}
                    <div class="card border-0 shadow mb-5">
                        <div class="card-header py-4 border-0">
                            <div class="media align-items-center">
                                <div class="media-body">
                                    <p class="subtitle text-sm text-primary">Squeeze out the best</p>
                                    <h4 class="mb-0">Make us of Hostelz.com</h4>
                                </div>
                                <svg width="50" height="50"><use xlink:href="#compass"></use></svg>
                            </div>
                        </div>
                        <div class="pb-5 card-body">
                            <p class="font-weight-bold">How does Hostelz.com's booking system work?</p>
                            <p>Hostelz.com doesn't have a booking system.</p>
                            <p>We have partnerships with the major hostel booking (Hostelworld, Booking.com etc.). That allows our users to search for available beds in all of the major hostel booking sites at once and compare prices.</p>
                            <p>You do not need to list your available beds on Hostelz.com.</p>

                            <p class="font-weight-bold">Increase your Reservations</p>
                            <p>You can increase your reservations with us by keeping your information up to date. Please enter your description, photos, video and facilities into our system. This will help to list your hostel higher and receive more reservations from Hostelz.com Users.</p>
                        </div>
                    </div>
                    {{-- Card 3 --}}
                    <div class="card border-0 shadow mb-5">
                        <div class="card-header py-4 border-0">
                            <div class="media align-items-center">
                                <div class="media-body">
                                    <p class="subtitle text-sm text-primary">There is more...</p>
                                    <h4 class="mb-0">Other Online Resources</h4>
                                </div>
                                <svg width="50" height="50"><use xlink:href="#strategy"></use></svg>
                            </div>
                        </div>
                    <div class="pb-5 card-body">
                        <p class="font-weight-bold">Useful Hostelz.com resources for owners and managers:</p>
                        <ul>
                            <li><a class="text-info" target="_blank" href="{!! routeURL('articles', 'hostel-owner-suggestions') !!}">@langGet('mgmt.SuggestionsForHostelOwners')</a> - Suggestions based on our experience and feedback from Hostelz.com users.</li>
                        </ul>
                         
                        <p class="font-weight-bold">Other useful online resources:</p>
                        <ul>
                            <li><a class="text-info" target="_blank" href="http://www.hostelmanagement.com/?utm_campaign=platform&utm_source=hostelz">Hostel Management</a> - The online resource for hostel professionals and enthusiasts.</li>

                            {{-- (talking to them now about maybe doing a promotion again
                            <li><a href="http://www.hosteloffice.com/?utm_campaign=platform&utm_source=hostelz&utm_medium=hostelz-ad">HostelOffice</a> - Hostel management software that automates your allocation of beds in multiple booking systems.</li>
                            --}}
                            {{-- They agreed to exchange links with us.  Removed 12.08.2021 by Matt --}}
                            {{-- <li><a target="_blank" href="https://nobeds.com/?utm_campaign=platform&utm_source=hostelz">NOBEDS</a> - A free and easy to use hotel ?? property management system.</li> --}}
                            
                            {{-- Negotiating a deal with them; 31.03.2020 - Matt 
                            <li><a target="_blank" href="https://www.frontdeskmaster.io/?utm_campaign=platform&utm_source=hostelz">FrontDesk Master</a></li>
                            --}}
                            
                            {{-- They're paying $300 per 6 months for a link here // Deactivated the link 31.03.2020 as they do not get much out of it and do not pay anymore.
                            <li><a target="_blank" href="https://bananadesk.com/?utm_campaign=platform&utm_source=hostelz&utm_medium=hostelz-ad">BananaDesk</a> - a complete cloud-based front desk and property management solution for hostels.</li>
                        	--}}
                        </ul>
                    </div>
                </div>
                </div>
    </div>
</div>
    {{-- {#AnyQuestions#|replace:"<a>":"<a href=$webURL$LANG_URL_PREFIX/contact.php?contactType=listings>"} --}}
@stop

@section('pageBottom')
    @parent
    <script>
      initializeTopHeaderSearch();
    </script>
@stop