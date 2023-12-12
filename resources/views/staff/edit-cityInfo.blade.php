<?php

use App\Models\CityInfo;

Lib\HttpAsset::requireAsset('autocomplete');
?>

@extends('staff/edit-layout')

@section('aboveForm')

	@if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

		<div class="navLinksBox">
			<ul class="nav nav-pills">
				<li><a href="{!! $formHandler->model->getURL() !!}">View City</a></li>
				@if (auth()->user()->hasPermission('admin'))
					<li><a class="objectCommandPostFormValue" data-object-command="searchRank" href="#">Search Rank</a>
					</li>
				@endif
				<li><a href="https://en.wikipedia.org/w/wiki.phtml?search={!! urlencode($formHandler->model->city) !!}">Wikipedia
						Search</a></li>
				<li>
					<a href="http://wikitravel.org/wiki/en/index.php?go=Go&search={!! urlencode($formHandler->model->city) !!}">Wikitravel
						Search</a></li>
				<li>
					<a href="https://www.google.com/search?q={!! urlencode($formHandler->model->city.', '.$formHandler->model->country) !!}">Google
						Search</a></li>
				{{-- <a href="/admin/gpx.php?cityID={$qf->data.id}">GPX</a> --}}
				<li><a class="objectCommandPostFormValue" data-object-command="updateGeocoding" href="#">Update
						Geocoding Info</a></li>
				<li><a class="objectCommandPostFormValue" data-object-command="updateSpecialListings" href="#">Update
						SpecialListings</a></li>
			</ul>
		</div>

	@endif

@stop

@section('nextToForm')

	@if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

		<div class="list-group">
			<a href="#" class="list-group-item active">Related</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}"
			   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!}
				History</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-listings', [ 'city' => $formHandler->model->city, 'region' => $formHandler->model->region, 'country' => $formHandler->model->country ]) !!}"
			   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!}
				Listings</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'cityInfo', 'subjectID' => $formHandler->model->id ]) !!}"
			   class="list-group-item"><span
						class="pull-right">&raquo;</span>{!! langGet('Staff.icons.AttachedText') !!} Descriptions</a>
			<a href="{!! routeURL('staff-cityInfo-pics', $formHandler->model->id) !!}" class="list-group-item"><span
						class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Pic') !!} City Pics</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-cityComments', [ 'cityID' => $formHandler->model->id ]) !!}"
			   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.CityComment') !!}
				City Comments</a>
			<a href="#" class="objectCommandPostFormValue list-group-item" data-object-command="geocodedInfo"><span
						class="pull-right">&raquo;</span><i class="fa fa-map-pin"></i> Geocoded Info</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-useGeocodingInfo', [ 'city' => $formHandler->model->city, 'region' => $formHandler->model->region, 'country' => $formHandler->model->country ], 'search', 'list') !!}"
			   class="list-group-item"><span class="pull-right">&raquo;</span><i class="fa fa-map-pin"></i> Use
				Geocoding Info</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-incomingLinks', [ 'placeID' => $formHandler->model->id, 'placeType' => 'CityInfo' ], 'search') !!}"
			   class="list-group-item"><span class="pull-right">&raquo;</span><i class="fa fa-map-pin"></i> Incoming
				Links</a>

			@if (auth()->user()->hasPermission('admin'))
				<a href="{!! Lib\FormHandler::searchAndListURL('staff-ads', [ 'placeID' => $formHandler->model->id, 'placeType' => 'CityInfo' ], 'search') !!}"
				   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Ad') !!} Ads</a>
				<a href="{!! Lib\FormHandler::searchAndListURL('staff-searchRank', [ 'placeID' => $formHandler->model->id, 'placeType' => 'CityInfo' ], 'search') !!}"
				   class="list-group-item"><span
							class="pull-right">&raquo;</span>{!! langGet('Staff.icons.SearchRank') !!} Search Ranks</a>
			@endif

		</div>

		<div class="list-group">
			<a href="#" class="list-group-item active">SEO</a>

			@if($formHandler->model->slp->count())
				<a href="{!! routeURL('slpStaff:index', ['search' => ['city' => $formHandler->model->city]]) !!}"
				   class="list-group-item">
					<span class="pull-right">&raquo;</span>SLPs
				</a>
			@endif

			@if($formHandler->model->districts->count())
				<a href="{!! routeURL('staff:district:index', ['search' => ['city' => $formHandler->model->city]]) !!}"
				   class="list-group-item">
					<span class="pull-right">&raquo;</span>Districts
				</a>
			@endif
		</div>

		<div class="list-group">
			<a href="#" class="list-group-item active">Category Page's</a>

			@foreach($categoryPages as $categoryPage)
				<a href="{{ $categoryPage->categoryUrl }}" class="list-group-item">
					{{ $categoryPage->category->fullName() }}
					({{ $categoryPage->listingsCount }})
					<span class="pull-right">&raquo;</span>
				</a>
			@endforeach

		</div>

		<div class="list-group">
			<a href="#" class="list-group-item active">Category Page's Description</a>

			@foreach($categoryPages as $categoryPage)
				<a href="{{ $categoryPage->editLink }}" class="list-group-item">
					<b>edit</b> {{ $categoryPage->category->fullName() }}
					<span class="pull-right">&raquo;</span>
				</a>
			@endforeach

		</div>

	@endif

@stop


@section('belowForm')

	{{--
	@if ($formHandler->mode == 'searchAndList')

		<p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>

	@elseif ($formHandler->mode == 'editableList')

		<p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>

	@endif
	--}}

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

				{{-- Autocomplete CityGroup --}}
                $('.formHandlerForm input[name="data[cityGroup]"]').devbridgeAutocomplete({
                    serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                    paramName: 'search',
                    params: {
                        'field': 'cityGroup',
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
