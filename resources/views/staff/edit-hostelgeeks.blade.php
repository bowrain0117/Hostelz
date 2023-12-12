<?php

use App\Models\User;

Lib\HttpAsset::requireAsset('autocomplete');

$listing = $formHandler->model;
?>

@extends('staff/edit-layout', ['itemName' => 'Hostelgeeks'])

@section('aboveForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="{!! $listing->getURL() !!}">View Listing</a></li>
                @if (!$listing->isLive())
                    <li><a href="{!! routeURL('staff-previewListing', $listing->id) !!}">Preview Listing</a></li>
                @endif
                <li><a class="objectCommandPostFormValue" data-object-command="updateListing" href="#">Update
                        Listing</a></li>
                @if ($listing->getBestEmail('listingIssue') != '')
                    <li>
                        <a href="{!! routeURL('staff-mailMessages', 'new') !!}?composingMessage[recipient]={{{ $listing->getBestEmail('listingIssue') }}}&listingID={!! $listing->id !!}">Send
                            Email</a></li>
                @endif
                <li><a href="https://www.google.com/search?q={!! urlencode($listing->name . ', ' . $listing->city) !!}"
                       target="_blank">Google</a></li>
                <li><a href="{!! routeURL('staff-mergeListings', [ 'add', $listing->id ]) !!}">Add to Merge List</a>
                </li>
                @if (auth()->user()->hasPermission('admin'))
                    <li><a class="objectCommandPostFormValue" data-object-command="searchRank" href="#">Search Rank</a>
                    </li>
                @endif
                @if ($listing->web != '')
                    <li><a href="{{{ $listing->web }}}" rel="noreferrer" target="_blank">{{{ $listing->web }}}</a></li>
                @endif
                <li><a href="{!! routeURL('staff-listings-checkAvailability', [$listing->id]) !!}">Check
                        Availability</a></li>

            </ul>
        </div>

    @endif

@stop

@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

            <?php
            $isLiveOrWhyNot = $listing->isLiveOrWhyNot() ?>
        @if ($isLiveOrWhyNot === 'live')
            <strong><span class="text-success">{!! langGet('Listing.isLiveOrWhyNot.live') !!}</span></strong>
        @elseif($isLiveOrWhyNot === 'noBookingSystemAndNoValidWebsite')
            <div role="alert" class="alert alert-warning">
                {!! langGet('Listing.isLiveOrWhyNot.'.$isLiveOrWhyNot) !!}
                <hr>
                <ul style="padding-left: 10px;">
                    <li>Shown as closed</li>
                    <li>Not listed in city</li>
                    @if(!str_contains($listing->getURL(), '/+'))
                        <li>Indexed on Google</li>
                    @endif
                </ul>
            </div>
        @else
            <div role="alert" class="alert alert-warning">
                {!! langGet('Listing.isLiveOrWhyNot.'.$isLiveOrWhyNot) !!}
            </div>
        @endif

        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $listing->id ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!}
                History</a>
            <a href="#" class="objectCommandPostFormValue list-group-item" data-object-command="geocodedInfo"><span
                        class="pull-right">&raquo;</span><i class="fa fa-map-pin"></i> Geocoded Info</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-bookings', [ 'listingID' => $listing->id ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Booking') !!}
                Bookings</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-ratings', [ 'hostelID' => $listing->id ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Rating') !!}Guest
                Ratings</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'hostelID' => $listing->id ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Review') !!}
                Reviews</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'listingID' => $listing->id ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!}
                Emails</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'hostels', 'subjectID' => $listing->id ]) !!}"
               class="list-group-item"><span
                        class="pull-right">&raquo;</span>{!! langGet('Staff.icons.AttachedText') !!} Attached Text</a>

            @if (auth()->user()->hasPermission('admin'))
                {{-- <a href="#" class="list-group-item"><span class="pull-right">&raquo;</span> Sticker/Award</a> --}}
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-searchRank', [ 'placeID' => $formHandler->model->id, 'placeType' => 'Listing' ], 'search') !!}"
                   class="list-group-item"><span
                            class="pull-right">&raquo;</span>{!! langGet('Staff.icons.SearchRank') !!} Search Ranks</a>
            @endif

            <a href="{!! Lib\FormHandler::searchAndListURL('staff-cityInfos', [ 'city' => $listing->city, 'region' => $listing->region, 'country' => $listing->country ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.CityInfo') !!}
                City Info</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-listings', [ 'city' => $listing->city, 'region' => $listing->region, 'country' => $listing->country ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!}Same
                City Listings</a>
        </div>

        <div class="list-group">
            <a href="#" class="list-group-item active">Listing Management</a>
            @if (auth()->user()->hasPermission('staffEditUsers'))
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-users', [ 'mgmtListings' => $listing->id ]) !!}"
                   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!}
                    Management Users</a>
            @endif
            @foreach ([ 'features', 'description', 'location', 'mapLocation', 'pics', 'panoramas', 'video', 'backlink' ] as $manageAction)
                <a href="{!! routeURL('staff-listing-manage', [ $listing->id, $manageAction ]) !!}"
                   class="list-group-item"><span
                            class="pull-right">&raquo;</span>{!! langGet('ListingEditHandler.icons.'.$manageAction) !!} {!! langGet('ListingEditHandler.actions.'.$manageAction) !!}
                </a>
            @endforeach
        </div>


        @if ($listing->cityInfo && $listing->cityInfo->hostelCount >= min(\App\Models\CityInfo::SPECIAL_HOSTELS_PAGE_MIN_HOSTELS))
            <div class="list-group">
                <a href="#" class="list-group-item active">SEO</a>
                @if ($listing->cityInfo->hostelCount>= \App\Models\CityInfo::SPECIAL_HOSTELS_PAGE_MIN_HOSTELS['cheap'])
                    <a href="{!! routeURL('staff-listingSpecialText', 'edit-or-create')."?listingID=$listing->id&type=cheapHostels" !!}"
                       class="list-group-item"><span class="pull-right">&raquo;</span>Cheap Hostels Text</a>
                @endif
                @if ($listing->cityInfo->hostelCount >= \App\Models\CityInfo::SPECIAL_HOSTELS_PAGE_MIN_HOSTELS['best'])
                    <a href="{!! routeURL('staff-listingSpecialText', 'edit-or-create')."?listingID=$listing->id&type=bestHostels" !!}"
                       class="list-group-item"><span class="pull-right">&raquo;</span>Best Hostels Text</a>
                @endif
                {{-- 
                @if ($listing->cityInfo->hostelCount >= \App\Models\CityInfo::SPECIAL_HOSTELS_PAGE_MIN_HOSTELS['party'])
                    <a href="{!! routeURL('staff-listingSpecialText', 'edit-or-create')."?listingID=$listing->id&type=partyHostels" !!}" class="list-group-item"><span class="pull-right">&raquo;</span>Party Hostels Text</a>
                @endif
                --}}
            </div>
        @endif

        <div class="list-group">
            <a href="#" class="list-group-item active">{!! langGet('Staff.icons.Imported') !!} Imported</a>
            @if ($listing->importeds->isEmpty())
                <a href="#" class="list-group-item disabled">(none)</a>
            @else
                @foreach ($listing->importeds as $imported)
                    <a href="{!! routeURL('staff-importeds', $imported->id) !!}" class="list-group-item">
                        <div @if ($imported->status == 'inactive') class="text-muted" @endif>
                            <div>
                                <strong>{!! $imported->getImportSystem()->displayName !!} @if ($imported->status == 'inactive')
                                        (inactive)
                                    @endif </strong></div>
                            <div><em>{!! $imported->name !!}</em></div>
                            <div>{!! $imported->address1 . ($imported->address2 != '' ? ', '.$imported->address2 : '') !!}</div>
                            <div>{{{ $imported->city }}}</div>
                        </div>
                    </a>
                @endforeach
            @endif
        </div>

            <?php
            $listingDuplicates = \App\Models\Listing\ListingDuplicate::forListingID($listing->id)->where(
                'status',
                '!=',
                'nonduplicates'
            )->get(); ?>
        @if (!$listingDuplicates->isEmpty())
            <br>
            <div class="list-group">
                <a href="#" class="list-group-item disabled">{!! langGet('Staff.icons.ListingDuplicate') !!} <strong>Possible
                        Duplicates</strong></a>
                @foreach ($listingDuplicates as $duplicate)
                        <?php
                        $duplicateListing = ($duplicate->listingID == $listing->id ? $duplicate->otherListingListing : $duplicate->listing); ?>
                    <a href="{!! routeURL('staff-mergeListings', [ 'showThese', $duplicate->listingID.','.$duplicate->otherListing ]) !!}"
                       class="list-group-item">
                        {!! $duplicateListing->name !!} ({!! $duplicate->status !!} {!! $duplicate->score !!}%)
                    </a>
                @endforeach
            </div>
        @endif

    @endif

@stop


@section('belowForm')

    @if ($formHandler->mode == 'updateForm')
        <div class="row pb-5">
            <div class="col-md-10 text-center">
                {{-- (Handled by javascript in edit-layout.blade.php) --}}
                @if ($listing->verified < \App\Models\Listing\Listing::$statusOptions['ok'])
                    <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[verified]"
                            data-value-of-field="{!! \App\Models\Listing\Listing::$statusOptions['ok'] !!}">Approve
                    </button>
                @endif
                @if ($listing->verified == \App\Models\Listing\Listing::$statusOptions['new'])
                    <button class="btn btn-warning setValueAndSubmit" data-name-of-field="data[verified]"
                            data-value-of-field="{!! \App\Models\Listing\Listing::$statusOptions['newIgnored'] !!}">
                        Ignore
                    </button>
                @endif
            </div>
        </div>
        <div class="row pb-5">
            <p><b>Listing management sign-up link:</b></p>
            <pre class="mb-3 bg-light p-3 rounded">{{{ User::mgmtSignupURL($listing->id, 'en') }}}</pre>
        </div>

    @elseif ($formHandler->mode == 'searchAndList' || $formHandler->mode == 'list' )
        <div class="text-right">
            <a href="{!! currentUrlWithQueryVar(['getKML'=> 1], ['page']) !!}"><i class="fa fa-download"></i> &nbsp;Download
                a KML file of these listings</a>
        </div>
    @endif

@stop


@section('pageBottom')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'insertForm')

        <script>

          $(document).ready(function () {
              {{-- Autocomplete Country --}}
              $('.formHandlerForm input[name="data[country]"]').devbridgeAutocomplete({
                serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                paramName: 'search',
                params: {
                  'field': 'country'
                },
                minChars: 1,
                triggerSelectOnValidInput: false, // keeps it from auto-correcting capitalization
                deferRequestBy: 100 {{-- wait briefly to see if they hit another character before querying --}}
              });

              {{-- Autocomplete Region --}}
              $('.formHandlerForm input[name="data[region]"]').devbridgeAutocomplete({
                serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                paramName: 'search',
                params: {
                  'field': 'region',
                  'context[country]': function () {
                    return $('.formHandlerForm [name="data[country]"]').val();
                  },
                },
                minChars: 1,
                triggerSelectOnValidInput: false, // keeps it from auto-correcting capitalization
                deferRequestBy: 100 {{-- wait briefly to see if they hit another character before querying --}}
              });

              {{-- Autocomplete City --}}
              $('.formHandlerForm input[name="data[city]"]').devbridgeAutocomplete({
                serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                paramName: 'search',
                params: {
                  'field': 'city',
                  'context[country]': function () {
                    return $('.formHandlerForm [name="data[country]"]').val();
                  },
                  'context[region]': function () {
                    return $('.formHandlerForm [name="data[region]"]').val();
                  }
                },
                minChars: 1,
                triggerSelectOnValidInput: false, // keeps it from auto-correcting capitalization
                deferRequestBy: 100 {{-- wait briefly to see if they hit another character before querying --}}
              });

              {{-- Autocomplete cityAlt --}}
              $('.formHandlerForm input[name="data[cityAlt]"]').devbridgeAutocomplete({
                serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                paramName: 'search',
                params: {
                  'field': 'cityAlt',
                  'context[country]': function () {
                    return $('.formHandlerForm [name="data[country]"]').val();
                  },
                  'context[city]': function () {
                    return $('.formHandlerForm [name="data[city]"]').val();
                  }
                },
                minChars: 1,
                triggerSelectOnValidInput: false, // keeps it from auto-correcting capitalization
                deferRequestBy: 100 {{-- wait briefly to see if they hit another character before querying --}}
              });

              {{--
                  (temp?) fix of issue where autocomplete causes the browser not to remember values the user entered when they go back to the form
                  See https://github.com/devbridge/jQuery-Autocomplete/issues/393.
              --}}
              $(window).bind('beforeunload', function () {
                $('.formHandlerForm input').removeAttr("autocomplete");
              });

          });

        </script>

    @endif

    @parent

@stop