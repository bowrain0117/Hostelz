<?php

use App\Models\Languages;

?>

@extends('layouts/admin')

@section('title', 'Team Menu - Hostelz.com')

@section('content')

    <div class="pt-3 pb-5 container">
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="breadcrumbs">
                    <ol class="breadcrumb black" typeof="BreadcrumbList">
                        {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                        {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
                        {!! breadcrumb('Staff') !!}
                    </ol>
                </div>
            </div>

            @if (isset($diskInfo) && auth()->user()->hasPermission('admin'))
                <div class="col-12 col-md-6">
                    <div class="pull-right">
                        <a href="{{ routeURL('staff-menu') }}?command=showDiscUsage">
                            Free space <b class="{{ $diskInfo['classTextColor'] }}">{{$diskInfo['freeSpace']}}
                                ({{ $diskInfo['freeSpacePercentage'] }}%)</b>
                            of {{$diskInfo['totalSpace']}}</a>
                        <br>
                        <span>Pics Spaces: {{ $diskInfo['picsSpace'] }}</span>
                    </div>
                </div>
            @endif

        </div>
        @if ($message != '')
            <br>
            <div class="well">{!! $message !!}</div>
        @endif

        <h1>Team Menu</h1>
        <div class="row threeColumns" style="margin-bottom: 50px;">

            <div class="col-md-12">

                @if (auth()->user()->hasPermission('staffEmail'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Email</div>
                        <ul class="list-group">
                            <li class="list-group-item"><a class="text-warning"
                                                           href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'userID' => auth()->id(), 'status' => 'new', 'spamFilter' => true ], 'search') !!}">{!! langGet('Staff.icons.MailMessage') !!}
                                    Inbox</a></li>
                            <li class="list-group-item">
                                <a class="text-warning"
                                   href="{!! routeURL('staff-mailMessages') !!}?where[userID]={!! auth()->id() !!}&where[reminderDate][min]=2000-01-01&where[reminderDate][max]={!! date('Y-m-d') !!}&mode=searchAndList">{!! langGet('Staff.icons.MailMessage') !!}
                                    Reminder Emails Due</a>
                                (<a href="{!! routeURL('staff-mailMessages') !!}?where[userID]={!! auth()->id() !!}&where[reminderDate][min]=2000-01-01&mode=searchAndList">see
                                    all</a>)
                            </li>
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-mailMessages') !!}?where[userID]={!! auth()->id() !!}&where[status]=hold&mode=searchAndList"><i
                                            class="fa fa-clock-o"></i>Held Mail</a></li>
                            <li class="list-group-item"><a href="{!! routeURL('staff-mailMessages', 'new') !!}"><i
                                            class="fa fa-pencil-square-o"></i>Compose New</a></li>
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-mailMessages') !!}?where[userID]={!! auth()->id() !!}&where[status]=outgoingQueue&mode=searchAndList"><i
                                            class="fa fa-envelope-o"></i>Sending Queue</a></li>
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-mailMessages') !!}?where[userID]={!! auth()->id() !!}&where[status]=outgoing&mode=searchAndList"><i
                                            class="fa fa-envelope-o"></i>Sent Mail</a></li>
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-mailMessages') !!}?where[userID]={!! auth()->id() !!}&where[status]=archived&mode=searchAndList"><i
                                            class="fa fa-envelope-o"></i>Archived Mail</a></li>
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-mailMessages') !!}?search[spamFilter]=1"><i
                                            class="fa fa-search"></i>Search Emails</a></li>
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-my-macros') !!}?where[purpose]=mail">{!! langGet('Staff.icons.Macro') !!}
                                    My Email Macros</a></li>
                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item"><a href="?command=mailFetch"><i class="fa fa-gears"></i>Fetch
                                        New</a></li>
                                <li class="list-group-item"><a href="?command=mailSendQueued"><i
                                                class="fa fa-gears"></i>Send Queued Mail</a></li>
                                <li class="list-group-item"><a class="text-warning"
                                                               href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'status' => 'new', 'spamFilter' => true ], 'search') !!}&search[transmitTime][max]={{{ Carbon::now()->subDays(30)->format('Y-m-d') }}}">{!! langGet('Staff.icons.MailMessage') !!}
                                        Old Emails (all mailboxes)</a></li>
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-macros') !!}">{!! langGet('Staff.icons.Macro') !!}
                                        All Email Macros</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('staffEditHostels'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Listings</div>
                        <ul class="list-group">

                            <li class="list-group-item"><a href="@routeURL('staff-listingsInstructions')"><i
                                            class="fa fa-question"></i>Instructions</a></li>
                            <li class="list-group-item"><a href="{!! routeURL('staff-listings') !!}"><i
                                            class="fa fa-search"></i>Search Listings</a></li>
                            <li class="list-group-item"><a href="{!! routeURL('staff-useGeocodingInfo') !!}"><i
                                            class="fa fa-map-pin"></i>Use Geocoding Info</a></li>
                            <li class="list-group-item"><a href="{!! routeURL('staff-hostelsChain') !!}"><i
                                            class="fa fa-share-alt" aria-hidden="true"></i>Hostels Chains</a></li>
                            <li class="list-group-item">
                                <a class="text-warning"
                                   href="{!! routeURL('staff-listingCorrections') !!}?search[verified]={!! \App\Models\Listing\Listing::$statusOptions['listingCorrection'] !!}&mode=list">{!! langGet('Staff.icons.Listing') !!}
                                    Listing Corrections</a>
                                (<a class="text-warning"
                                    href="{!! routeURL('staff-listingCorrections') !!}?search[verified]={!! \App\Models\Listing\Listing::$statusOptions['listingCorrectionFlagged'] !!}&mode=list">flagged</a>)
                            </li>
                            {{-- No longer assigned duplicated "due" (that was something Kristy preferred)
                            <li class="list-group-item">
                                <a class="text-warning" href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[userID]={!! auth()->id() !!}&mode=searchAndList&showMergeLinks=1">{!! langGet('Staff.icons.ListingDuplicate') !!}Duplicates Due</a>
                            </li>
                            --}}
                            <li class="list-group-item">
                                {!! langGet('Staff.icons.ListingDuplicate') !!}Duplicates:
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[score][min]=75&search[status]=suspected&search[propertyType]=Hostel&mode=searchAndList&showMergeLinks=1">Hostels</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[score][min]=85&search[maximumChoiceDifficulty]=1&mode=searchAndList&showMergeLinks=1">Simplest</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[score][min]=90&mode=searchAndList&showMergeLinks=1">90%</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[score][min]=85&mode=searchAndList&showMergeLinks=1">85%</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[score][min]=80&mode=searchAndList&showMergeLinks=1">80%</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=suspected&search[score][min]=75&mode=searchAndList&showMergeLinks=1">75%</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=hold&search[userID]={!! auth()->id() !!}&mode=searchAndList&showMergeLinks=1">On
                                    Hold</a> -
                                @if (auth()->user()->hasPermission('admin'))
                                    <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=hold&mode=searchAndList&showMergeLinks=1">On
                                        Hold (all users)</a> -
                                @endif
                                <a href="{!! routeURL('staff-listingDuplicates') !!}?search[status]=flagged&mode=searchAndList&showMergeLinks=1">Flagged</a>
                                -
                                <a href="{!! routeURL('staff-listingDuplicates') !!}">Search</a>
                            </li>
                            <li class="list-group-item"><a href="{!! routeURL('staff-listingSpiderVideos') !!}"
                                                           class="text-warning"><i class="fa fa-film"></i>Approve Videos</a>
                            </li>
                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('admin'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Imported</div>
                        <ul class="list-group">
                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-importeds') !!}">{!! langGet('Staff.icons.Imported') !!}
                                    Imported Database</a></li>
                            <li class="list-group-item">
                                <ul>
                                    @foreach (\App\Services\ImportSystems\ImportSystems::allActive() as $systemName => $systemInfo)
                                        <li>
                                            {{{ $systemInfo->shortName() }}}:
                                            @if ($systemInfo->affiliatePortal != '')
                                                <a href="{!! $systemInfo->affiliatePortal !!}">Affiliate Portal</a>
                                            @endif
                                            <a href="{!! routeURL('staff-importeds').'?mode=searchAndList&search[status]=active&search[system]='.$systemName !!}">Our
                                                Data</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                            <li class="list-group-item">
                                <i class="fa fa-gears"></i><a href="{!! routeURL('staff-imports')!!}">Imports</a>
                            </li>
                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('staffMarketing'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Marketing</div>
                        <ul class="list-group">
                            <li class="list-group-item">
                                {!! langGet('Staff.icons.IncomingLink') !!}Incoming Links:
                                <a href="@routeURL('staff-incomingLinksInstructions')">Instructions</a> -
                                <a href="@routeURL('staff-incomingLinks')">Search</a> -
                                <a href="@routeURL('staff-incomingLinks-new')">Create New</a> -
                                @if (auth()->user()->hasPermission('admin'))
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-incomingLinks', [ 'contactStatus' => 'flagged' ])!!}"
                                       class="text-warning">Flagged</a> -
                                    <a href="@routeURL('admin-importIncomingLinks')">Import</a> -
                                    <a href="{!! routeURL('staff-incomingLinks') !!}?specialCommand=assignTodoLinksToMarketingUsers">assignTodoLinksToMarketingUsers</a>
                                    -
                                @endif
                                <a href="{!! Lib\FormHandler::searchAndListURL('staff-incomingLinks', [ 'userID' => auth()->id(), 'contactStatus' => 'todo' ])!!}"
                                   class="text-warning">Todo</a> -
                                <a href="{!! routeURL('staff-incomingLinksFollowUp') !!}" class="text-warning">Initial
                                    Contact Follow-ups</a> -
                                <a href="{!! routeURL('staff-incomingLinks') !!}?where[userID]={!! auth()->id() !!}&where[contactStatus]=discussing&mode=searchAndList"
                                   class="text-warning">@langGet('IncomingLink.forms.options.contactStatus.discussing')</a>
                                -
                                <a href="{!! routeURL('staff-incomingLinks') !!}?where[userID]={!! auth()->id() !!}}&where[reminderDate][min]=2000-01-01&where[reminderDate][max]={!! date('Y-m-d') !!}&mode=searchAndList"
                                   class="text-warning">Reminder Due</a>
                            </li>
                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('admin') || auth()->user()->hasPermission('staffBookings'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Reports & Stats</div>
                        <ul class="list-group">

                            @if (auth()->user()->hasPermission('staffBookings'))
                                {{-- Email support people need to access the bookings --}}
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-bookings') !!}">{!! langGet('Staff.icons.Booking') !!}
                                        Bookings</a></li>
                            @endif
                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-eventLogs') !!}">{!! langGet('Staff.icons.EventLog') !!}
                                        Event Log</a></li>
                                <li class="list-group-item">
                                    <a href="https://www.google.com/analytics/web/"><i class="fa fa-line-chart"></i>Google
                                        Analytics</a> -
                                    <a href="https://www.google.com/webmasters/tools/">Webmaster Tools</a> -
                                    <a href="https://code.google.com/apis/console/">API Stats</a>
                                </li>
                                <li class="list-group-item"><a href="@routeURL('taxReports')"><i
                                                class="fa fa-gears"></i>Tax Reports (for tax forms)</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('admin') || auth()->user()->hasPermission('staff'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Other</div>

                        <ul class="list-group">
                            @if (auth()->user()->hasPermission('staffEditUsers'))
                                <li class="list-group-item">
                                    <a href="{!! routeURL('staff-users') !!}">{!! langGet('Staff.icons.User') !!}
                                        Users</a>
                                    @if (auth()->user()->hasPermission('admin'))
                                        -
                                        <a href="{!! Lib\FormHandler::searchAndListURL('staff-users', [ 'access' => [ 'staff' ], 'status' => 'ok' ]) !!}">Staff</a>
                                        - <a href="{!! routeURL('staff-payAllUsers') !!}">Pay All</a>
                                    @endif
                                </li>
                            @endif

                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-ratings') !!}">{!! langGet('Staff.icons.Rating') !!}
                                        Guest Ratings</a></li>
                            @elseif (auth()->user()->hasPermission('staffEditComments'))
                                <li class="list-group-item"><a
                                            href="{!! Lib\FormHandler::searchAndListURL('staff-ratings', [ 'status' => 'new', 'emailVerified' => true, 'language' => Languages::currentCode() ]) !!}"
                                            class="text-warning">{!! langGet('Staff.icons.Rating') !!}Guest Ratings
                                        Marked for Editing</a></li>
                            @endif
                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item">
                                    <a href="{!! routeURL('staff-reviews') !!}">{!! langGet('Staff.icons.Review') !!}
                                        Reviews</a> -
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'status' => 'newReview', 'hasPics' => true, 'plagiarismCheckDate' => [ 'min' => '2000-01-01' ] ]) !!}"
                                       class="text-warning">New with Pics</a> -
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'status' => 'newReview', 'reviewDate' => [ 'max' => Carbon::now()->subDays(180)->format('Y-m-d') ] ]) !!}"
                                       class="text-warning">Old</a> -
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'status' => 'returnedReview', 'reviewDate' => [ 'max' => Carbon::now()->subDays(180)->format('Y-m-d') ] ]) !!}"
                                       class="text-warning">Old Returned</a> -
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'status' => 'deniedReview', 'reviewDate' => [ 'max' => Carbon::now()->subDays(180)->format('Y-m-d') ] ]) !!}"
                                       class="text-warning">Old Denied</a> -
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'newReviewerComment' => true ]) !!}"
                                       class="text-warning">New Comment</a>
                                    -
                                    <a href="{!! routeURL('staff-reviews').'?command=logPaymentsForAcceptedReviews' !!}">Log
                                        Payments for Accepted Reviews</a>
                                </li>
                            @elseif (auth()->user()->hasPermission('staffEditReviews'))
                                <li class="list-group-item">
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'status' => 'markedForEditing', 'language' => Languages::currentCode() ]) !!}"
                                       class="text-warning">{!! langGet('Staff.icons.Review') !!}Reviews Marked for
                                        Editing</a>
                                </li>
                            @endif
                            @if (auth()->user()->hasPermission('staffCityInfo'))
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-cityInfos') !!}">{!! langGet('Staff.icons.CityInfo') !!}
                                        Cities</a></li>
                            @endif
                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-cityComments') !!}">{!! langGet('Staff.icons.CityComment') !!}
                                        City Comments</a></li>
                            @elseif (auth()->user()->hasPermission('staffEditCityComments'))
                                <li class="list-group-item"><a
                                            href="{!! Lib\FormHandler::searchAndListURL('staff-cityComments', [ 'status' => 'new' ]) !!}"
                                            class="text-warning">{!! langGet('Staff.icons.CityComment') !!}City Comments
                                        Marked for Editing</a></li>
                            @endif

                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item">
                                    <a href="{!! routeURL('staff-attachedTexts') !!}">{!! langGet('Staff.icons.AttachedText') !!}
                                        Attached Text</a> -
                                    <a class="text-warning"
                                       href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'type' => 'description', 'status' => 'flagged' ]) !!}">Flagged</a>
                                </li>
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-articles') !!}">{!! langGet('Staff.icons.Article') !!}
                                        Articles</a></li>

                                @if (auth()->user()->hasPermission('admin'))
                                    <li class="list-group-item">
                                        <a href="@routeURL('documentation')"><i class="fa fa-question"></i>Documentation</a>
                                    </li>
                                @endif

                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-ads') !!}?mode=searchAndList">{!! langGet('Staff.icons.Ad') !!}
                                        Ads</a></li>
                                <li class="list-group-item">
                                    <a href="{!! routeURL('staff-countryInfos') !!}">{!! langGet('Staff.icons.CountryInfo') !!}
                                        Countries</a> -
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-countryInfos', [ 'regionType' => '' ]) !!}&comparisonTypes[regionType]=notEmpty"
                                       class="text-warning">Countries with Regions Displayed</a>
                                </li>
                                <li class="list-group-item">
                                    <a href="{!! routeURL('staff-dataCorrections') !!}">{!! langGet('Staff.icons.DataCorrection') !!}
                                        Data Correction</a>:
                                    <a href="{!! routeURL('staff-dataCorrection-mass', [ 'listings', 'country' ]) !!}">Listings
                                        Country</a> -
                                    <a href="{!! routeURL('staff-dataCorrection-mass', [ 'listings', 'region' ]) !!}">Listings
                                        Region</a> -
                                    <a href="{!! routeURL('staff-dataCorrection-mass', [ 'listings', 'cityAlt' ]) !!}">Listings
                                        Neighborhood</a>
                                </li>

                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-questionSets') !!}?mode=list">{!! langGet('Staff.icons.QuestionSet') !!}
                                        Question Sets</a></li>
                            @endif

                            @if (auth()->user()->hasPermission('staffEditAttached'))
                                <li class="list-group-item"><a
                                            href="@routeURL('staff-placeDescriptionEditingInstructions')">{!! langGet('Staff.icons.AttachedText') !!}
                                        City/Region/Country Description Editing Instructions</a></li>
                                <li class="list-group-item"><a class="text-warning"
                                                               href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'cityInfo', 'type' => 'description', 'status' => 'submitted', 'score' => 100, 'language' => Languages::currentCode() ]) !!}">{!! langGet('Staff.icons.AttachedText') !!}
                                        Approve New City Descriptions</a></li>
                                <li class="list-group-item"><a class="text-warning"
                                                               href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'countryInfo', 'type' => 'description', 'status' => 'submitted', 'score' => 100, 'language' => Languages::currentCode() ]) !!}">{!! langGet('Staff.icons.AttachedText') !!}
                                        Approve New Region/Country Descriptions</a></li>
                                <li class="list-group-item"><a class="text-warning"
                                                               href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'continentInfo', 'type' => 'description', 'language' => Languages::currentCode() ]) !!}">{!! langGet('Staff.icons.AttachedText') !!}
                                        Edit Continent Description</a></li>
                            @endif

                            @if (auth()->user()->hasPermission('staffPicEdit'))
                                <li class="list-group-item">
                                    <a href="@routeURL('staff-pics')">{!! langGet('Staff.icons.Pic') !!}Pics</a> -
                                    Fix Pics:
                                    (<a href="@routeURL('staff-picFix', 'instructions')">instructions</a>)
                                    - <a href="@routeURL('staff-picFix', 'reviews')">Review Photos</a>
                                    @if (auth()->user()->hasPermission('admin'))
                                        - <a href="@routeURL('staff-picFix', 'cityInfo')" class="text-warning">Cities
                                            (admin)</a>
                                        - <a href="@routeURL('staff-picFix', 'viewRecentReviewPicEdits')"
                                             class="text-warning">View recent review pic edits</a>
                                    @endif
                                </li>
                            @endif

                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('staffTranslation'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Translation</div>
                        <ul class="list-group">
                            @if (auth()->user()->hasPermission('admin'))
                                <li class="list-group-item">
                                    Admin: <a href="{!! routeURL('staff-languageStrings') !!}">Language Strings</a>
                                    - <a href="?command=updateLangFiles">Update Laravel Language Files</a>
                                </li>
                            @endif
                            @foreach (auth()->user()->countries as $language)
                                <li class="list-group-item"><a
                                            href="{!! routeURL('staff-translation', $language) !!}">{!! langGet('Staff.icons.LanguageString') !!}{!! \App\Models\Languages::get($language)->name !!}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (auth()->user()->hasPermission('developer'))

                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">Development</div>
                        <ul class="list-group">
                            <li class="list-group-item"><a href="{!! routeURL('staff-codeEditor') !!}"><i
                                            class="fa fa-desktop"></i>Code Editor</a></li>

                            {{--                            @if (!App::environment('production'))
                                                            --}}{{-- (Only set up to run on the dev site because we don't want to bother syncing node_modules to the production site) --}}{{--
                                                            <li class="list-group-item">
                                                                <a href="?command=gulp"><i class="fa fa-refresh"></i>Gulp</a>
                                                                (<a href="?command=gulpProduction">production mode</a>)
                                                            </li>
                                                        @else
                                                            <li class="list-group-item disabled">(Gulp must be run from dev server)</li>
                                                        @endif
                                                        @if (App::environment('local'))
                                                            <li class="list-group-item">
                                                                <i class="fa fa-upload"></i>Sync with Dev Server:
                                                                <a href="{!! routeURL('staff-devSync', [ 'local-download' ]) !!}">Download</a> -
                                                                <a href="{!! routeURL('staff-devSync', [ 'local-upload' ]) !!}">Upload</a>
                                                            </li>
                                                        @else
                                                            <li class="list-group-item"><a href="{!! routeURL('staff-devSync', [ 'dev' ]) !!}"><i class="fa fa-upload"></i>Sync Dev to Production Server</a></li>
                                                        @endif
                                                        <li class="list-group-item"><a href="{!! routeURL('staff-regenerateGeneratedImages') !!}"><i class="fa fa-refresh"></i>Regenerate Generated Images</a></li>--}}

                            <li class="list-group-item">
                                <i class="fa fa-globe"></i>Geonames:
                                <a href="{!! routeURL('staff-geonames') !!}">Search</a>
                                <!-- (this probably works as far as I know, but hasn't been tested in a long time and probably doesn't need to be used typically)
                                -
                                <a href="?command=geonamesDownload">Download</a> -
                                <a href="?command=geonamesImportDownloadedTest">Import Downloaded Data Test</a> -
                                <a href="?command=geonamesImportDownloaded">Import Downloaded Data</a>
                                (<a href="?command=geonamesImportDownloadedReset">reset parial import counters</a>)
                                -->
                            </li>
                            <li class="list-group-item"><a href="?command=clearAllPageCache"><i
                                            class="fa fa-refresh"></i>Clear Page Cache</a></li>

                            <li class="list-group-item"><a href="?command=phpInfo"><i
                                            class="fa fa-gears"></i>phpInfo</a></li>
                            <li class="list-group-item"><a href=?command=opcacheInfo><i class="fa fa-gears"></i>OpCache
                                    Info</a></li>
                            <li class="list-group-item"><a href=server-status><i
                                            class="fa fa-gears"></i>server-status</a> (normally disabled)
                            </li>
                            <li class="list-group-item"><a href="?command=showPhpErrors"><i class="fa fa-warning"></i>PHP
                                    Errors</a> (<a href="?command=clearPhpErrors">clear</a>)
                            </li>

                            <li class="list-group-item"><a href="{{ route('staff-laravel-logs') }}"><i
                                            class="fa fa-warning"></i>Laravel Errors</a></li>

                            {{--                            <li class="list-group-item"><a href="?command=showNotFoundErrors"><i class="fa fa-warning"></i>Not Found Errors</a> (<a href="?command=clearNotFoundErrors">clear</a>)</li>
                                                        <li class="list-group-item"><a href="?command=showNotFoundSecureErrors"><i class="fa fa-warning"></i>Secure Not Found Errors</a> (<a href="?command=clearNotFoundSecureErrors">clear</a>)</li>
                                                        <li class="list-group-item"><a href="?command=showServerErrors"><i class="fa fa-warning"></i>Server Errors</a> (<a href="?command=clearServerErrors">clear</a>)</li>--}}

                            <li class="list-group-item"><a href="?command=showDiscUsage"><i class="fa fa-hdd-o"></i>Disc
                                    usage</a></li>
                            <li class="list-group-item"><a href="?command=apiDocumentation"><i class="fa fa-hdd-o"></i>API
                                    documentation</a></li>

                            {{--                            <li class="list-group-item"><a href="?command=showSlowQueries"><i class="fa fa-warning"></i>Slow Queries</a> (<a href="?command=clearSlowQueries">clear</a>)</li>--}}
                        </ul>
                    </div>
                @endif

                <div class="panel menuOptionsPanel">
                    <div class="panel-heading">Your Info</div>
                    <ul class="list-group">
                        <li class="list-group-item"><a
                                    href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'userID' =>  auth()->id() ]) !!}"><i
                                        class="fa fa-gears"></i>Your History</a></li>
                        <li class="list-group-item"><a href="{!! routeURL('user:yourPay') !!}"><i
                                        class="fa fa-gears"></i>Your Pay</a></li>
                    </ul>
                </div>

                @if (auth()->user()->hasPermission('admin'))
                    <div class="panel menuOptionsPanel">
                        <div class="panel-heading">SEO Ninja</div>
                        <ul class="list-group">
                            <li class="list-group-item"><a href="{!! routeURL('seo:redirect') !!}"><i
                                            class="fa fa-arrow-circle-right"></i>301 Redirects</a></li>

                            <li class="list-group-item"><a href="{!! routeURL('seo:prettylink') !!}"><i
                                            class="fa fa-arrow-circle-right"></i>Prettylink</a></li>

                            <li class="list-group-item"><a href="{!! routeURL('slpStaff:index') !!}"><i
                                            class="fa fa-arrow-circle-right"></i>Special Landing Pages</a></li>

                            <li class="list-group-item"><a href="{!! routeURL('staff:district:index') !!}">
                                    {!! langGet('Staff.icons.CityInfo') !!}Districts</a></li>

                            <li class="list-group-item"><a
                                        href="{!! routeURL('staff-searchRank') !!}?mode=searchAndList">{!! langGet('Staff.icons.SearchRank') !!}
                                    Search Rank</a></li>

                            <li class="list-group-item"><a href="#"><i class="fa fa-font"></i>Meta Info (Titles and
                                    Description - coming soon)</a></li>
                        </ul>
                    </div>
                @endif

                <div class="panel menuOptionsPanel">
                    <div class="panel-heading">Hostelgeeks 🤓</div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <a href="{!! routeURL('staff-hostelgeeks') !!}">
                                <i class="fa fa-arrow-circle-right"></i>
                                Best Hostel Features
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
        <hr>
        @if (auth()->user()->hasPermission('admin'))
            <p><b>Documentation:</b> Here you can find the <a href="{!! routeURL('documentation') !!}">documentation on
                    several tasks</a>, functionalities and topics.</p>
            <hr>
        @endif

        @if (auth()->user()->hasPermission('developer'))
            <div class="well">
                <form>
                    <p>
                        <textarea class="form-control" name="inputBox">{{{ Request::input('inputBox') }}}</textarea>
                    </p>
                    <div>
                        <button class="btn btn-primary" type="submit" name="command" value="query">Query</button>
                        {{--
                            <button class="btn" type="submit" name="command" value="query">Query</button>
                            <button class="btn" type="submit" name="command" value="query">Query</button>
                        --}}
                    </div>
                </form>
            </div>
        @endif


        <p class="text-primary">Note: Items in <span class="text-warning">this color</span> are items that probably need
            to be checked on a regular basis.</p>

    </div>
@stop
