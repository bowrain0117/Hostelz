<?php

Lib\HttpAsset::requireAsset('staff.css');

?>

@extends('layouts/admin')

@section('title', "Merge Listings")

@section('header')
    <style>
        .mergeTable {
            font-size: 13px;
        }

        .bookingSystems td {
            font-size: 11px;
            padding-right: 5px;
        }

    </style>

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Merge Listings') !!}
        </ol>
    </div>

    <div class="container">

        @if ($isListingCorrection)
            <h1>Listing Correction Merge</h1>
        @else
            <h1>Listing Merge</h1>
        @endif

        {{-- Messages --}}

        @if ($message != '')
            <br>
            @if ($isSuccess)
                <div class="alert alert-success">
                    <h3>
                        <i class="fa fa-check-circle"></i> &nbsp;
                        {!! $message !!}
                    </h3>
                </div>
            @else
                <div class="well">
                    {!! $message !!}
                </div>
            @endif

        @endif

        @if ($listings)

            <table class="table table-hover table-striped mergeTable">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Status
                    @if ($mergeChoices && count($mergeChoices['propertyType']['choices']) > 1)
                        <th>Type</th>
                    @endif
                    <th>Address</th>
                    <th>City</th>
                    {{-- <th>Neighborhood</th> --}}
                    {{-- <th>Region</th> --}}
                    <th>Country</th>
                    <th>{!! langGet('Staff.icons.Imported') !!} Imported</th>
                    @if ($listingDuplicates)
                        <th>Merge Status</th>
                    @endif
                </tr>
                </thead>

                <tbody>

                @foreach ($listings as $listing)
                    <tr>
                        <td><a href="{!! $listing->getURL() !!}">{{{ $listing->name }}}</a></td>
                        <td>{!! $listing->formatForDisplay('verified') !!}</td>
                        @if ($mergeChoices && count($mergeChoices['propertyType']['choices']) > 1)
                            <td>{!! $listing->propertyType !!}</td>
                        @endif
                        <td>{{{ $listing->address }}}</td>
                        <td>{{{ $listing->city }}}</td>
                        {{-- <td>{{{ $listing->cityAlt }}}</td> --}}
                        {{-- <td>{{{ $listing->region }}}</td> --}}
                        <td>{{{ $listing->country }}}</td>
                        <td>
                            @if ($listing->importeds)
                                <table class="bookingSystems">
                                    @forelse ($listing->importeds as $imported)
                                        <tr>
                                            <td>
                                                <a href="{!! routeURL('staff-importeds', [ $imported->id ]) !!}" {!! $imported->status == 'active' ? '' : 'class="text-muted" style="text-decoration: line-through"' !!}>{!! $imported->getImportSystem()->shortName() !!}</a>
                                            </td>
                                            <td>{{{ $imported->name }}}</td>
                                            <td>{{{ $imported->address1 }}}, {{{ $imported->city }}}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td><span class="text-muted">(none)</span></td>
                                        </tr>
                                    @endforelse
                                </table>
                            @endif
                        </td>

                        @if ($listingDuplicates && !$listingDuplicates->isEmpty())
                            <td>
                                @foreach ($listingDuplicates as $duplicate)
                                    @if ($duplicate->listingID == $listing->id || $duplicate->otherListing == $listing->id)
                                        @if ($duplicate->status == 'nonduplicates')
                                            <div class="text-danger"><b>MARKED NONDUPLICATE!
                                                    (by {{{ $duplicate->user->username }}})</b>
                                                @elseif ($duplicate->status == 'hold')
                                                    On Hold
                                                @elseif (auth()->user()->hasPermission('admin'))
                                                    {{{ $duplicate->formatForDisplay('status') }}}
                                                    @if ($duplicate->score) - Score: {{{ $duplicate->score }}}
                                        @endif
                                    @endif
                                    @endif
                                @endforeach
                            </td>
                        @else
                            <td><span class="text-muted">(none)</span></td>
                        @endif

                        @if ($listing->verified == \App\Models\Listing\Listing::$statusOptions['unlisted'] || $listing->verified == \App\Models\Listing\Listing::$statusOptions['removed'])
                            <td class="text-danger"><strong>Note: Listing is removed.</strong></td>
                        @endif

                        @if ($listing->comment != '')
                            <td><strong>Notes:</strong> "{{{ mb_strimwidth($listing->comment, 0, 150, '...') }}}"</td>
                        @endif
                    </tr>

                @endforeach

                </tbody>
            </table>

            @if ($multipleListingsInSameImportedSystem)
                <div class="alert alert-warning"><strong>Note:</strong> Two or more of these listings are in the same
                    booking system (that may mean they aren't duplicates!).
                </div>
            @endif

            @if ($mergeChoices)

                <form method="post" class="center-block">
                    <input type="hidden" name="_token" value="{!! csrf_token() !!}">

                    @foreach ($listings as $listing)
                        <input type="hidden" name="mergeIDs[]" value="{!! $listing->id !!}">
                    @endforeach

                    <table style="border-spacing: 10px; border-collapse: separate;" id="mergeChoices">

                        @foreach ($mergeChoices as $fieldName => $field)

                            @if (count($field['choices']) > 1)

                                @if (\App\Models\Listing\ListingDuplicate::$listingFieldsMergeInfo[$fieldName]['mergeType'] == 'onlyOne')
                                    <tr>
                                        <td colspan=2 class="text-danger">Note:
                                            Multiple {!! \App\Models\Listing\Listing::getLabel($fieldName) !!} values
                                            may indicate these are non-duplicates!
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td><label class="radio-inline"
                                               style=""><b>{!! \App\Models\Listing\Listing::getLabel($fieldName) !!}
                                                :</b> &nbsp;</label></td>
                                    <td>

                                        @foreach ($field['choices'] as $valueKey => $value)

                                            <label class="radio-inline @if ($value == $field['default']) bg-info @endif">
                                                &nbsp;
                                                <input type="radio" name="choiceData[{!! $fieldName !!}]"
                                                       value="{{{ $value }}}"
                                                       @if ($value == $field['default']) CHECKED @endif >
                                                @if ($fieldName == 'web')
                                                    <a href="{!! $value !!}" target="_blank">{!! $value !!}</a>
                                                @else
                                                    {{{ \App\Models\Listing\Listing::formatValueForDisplay($fieldName, $value) }}}
                                                @endif

                                                &nbsp;

                                            </label>

                                            @if (in_array($fieldName, [ 'name', 'city', 'cityAlt', 'region' ]))
                                                <small><a href="https://www.google.com/search?q={!! urlencode($value) !!}"
                                                          class="text-muted">[g]</a></small>&nbsp;
                                            @endif

                                        @endforeach
                                    </td>
                                </tr>

                            @elseif (in_array($fieldName, [ 'address', 'city', 'cityAlt', 'region', 'zipcode', 'web' ]) && $field['choices'])

                                {{-- We always display these fields because it makes merging choices easier --}}
                                <tr>
                                    <td><label class="radio-inline"
                                               style=""><b>{!! \App\Models\Listing\Listing::getLabel($fieldName) !!}
                                                :</b> &nbsp;</label></td>
                                    <td>
                                            <?php
                                            $value = reset($field['choices']) ?>
                                        @if ($fieldName == 'web')
                                            <a href="{!! $value !!}" target="_blank">{!! $value !!}</a>
                                        @else
                                            {{{ \App\Models\Listing\Listing::formatValueForDisplay($fieldName, $value) }}}
                                        @endif
                                    </td>
                                </tr>

                            @endif

                        @endforeach
                    </table>

                    <br>

                    <div class="text-center">

                        <p>
                            <button class="btn btn-success" name="command" value="mergeNow">Merge Now!</button>
                        </p>

                        @if (!$isListingCorrection)
                            <p><a href="{!! routeURL('staff-mergeListings', 'clear') !!}">Clear Merge List</a></p>
                            <br>
                            <p><b>Notes:</b><br><textarea rows=4 cols=100 name="notes"
                                                          wrap="soft">{{{ $notes }}}</textarea>
                            <p>
                                {{-- (removed - have them email me instead)  <button class="btn btn-sm btn-sm btn-warning" name="command" value="flag">Flag for Admin</button> --}}
                                <button class="btn btn-sm btn-danger" name="command" value="hold">Hold</button>
                                <button class="btn btn-sm btn-default" name="command" value="nonduplicates">
                                    Non-Duplicates
                                </button>
                        @endif

                    </div>

                </form>

            @endif


            <br><br>

            @if ($contactEmailsString != '')

                <p>
                    <a href="{!! routeURL('staff-mailMessages', 'new') !!}?composingMessage[recipient]={!! urlencode($contactEmailsString) !!}&listingID={!! $listings->first()->id !!}&composingMessage[subject]={!! urlencode($listings->first()->name) !!}">Compose
                        Email to These Listings</a></li></p>

            @endif

            <div class="panel-group" id="mailPanels">

                {{-- Email History --}}

                <div class="panel panel-info">
                    <div class="panel-heading">
                        <div class="panel-title"><a data-toggle="collapse" href="#emailHistoryPanel">Email History of
                                These Listings</a></div>
                    </div>
                    <div id="emailHistoryPanel" class="panel-collapse collapse">
                        <div class="panel-body"></div>
                    </div>
                </div>

            </div>

        @else

            @if ($message == '')
                <br>
                <div class="well">No listings are currently in the merge list.</div>
            @endif

        @endif

    </div>

@stop

@section('pageBottom')

    <script>

        {{-- Highlight the selected choice --}}

        $('#mergeChoices input').change(function (event) {
          $(this).closest('td').find('label').removeClass('bg-info');
          $(this).closest('label').addClass('bg-info');
        });

        {{-- Mail History --}}

        $('#emailHistoryPanel').on('show.bs.collapse', function () {
          $('#emailHistoryPanel div.panel-body').html("[Loading]").load("?command=emailHistory");
        });

    </script>

    @parent

@endsection