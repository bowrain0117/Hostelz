<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', htmlspecialchars(langGet('searchResults.SearchResults', [ 'searchText' => $search ])) . ' - Hostelz.com')

@section('header')    

	<meta name="robots" content="noindex, nofollow">

@stop

@section('content')
<section class="pt-3 pb-5 container">
	<div class="row">
        <div class="col-12">
          	<!-- Breadcrumbs -->
        	<ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0">
        		{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            	{!! breadcrumb(langGet('searchResults.SearchResults', [ 'searchText' => $search ])) !!}
            </ul>
            <h1 class="hero-heading h2">{{{ langGet('searchResults.SearchResults', [ 'searchText' => $search ]) }}}</h1>
			<div class="p-md-2">
				<p class="">Below we list all matches fitting your search. From countries, regions, cities, and accommodations. We recommend using our powerful search engine to find your perfect hostel and compare prices. Find here <a href="{!! routeURL('allContinents') !!}" title="all hostels">all hostels in the world</a> by continent.</p>
				<div class="pt-4 text-center">
					<button class="btn-lg btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>	
				</div> 
			</div>
            @if (!$searchResults) 
            	<div class="well">{{{ langGet('searchResults.NoMatchesFor', [ 'search' => $search ]) }}}</div>
				<div class="p-md-2">
					<p class="">We recommend using our powerful search engine to find your perfect hostel and compare prices. Find here <a href="{!! routeURL('allContinents') !!}" title="all hostels">all hostels in the world</a> by continent.</p>
					<div class="pt-4 text-center">
						<button class="btn-lg btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>	
					</div> 
				</div>
        	@else
            @foreach ($searchResults as $type => $items)
            	<h2 class="mt-5 mb-3">@langGet('searchResults.'.$type.'Matches')</h2>
            	@foreach ($items as $item)
            		<p><a href="{!! $item['url'] !!}">{!! $type == 'cities' ? "<strong>$item[text]</strong>" : $item['text'] !!}</a>&nbsp; {!! $item['extraText'] !!}</p>
            	@endforeach    
            @endforeach              
        @endif
        </div>
    </div>
</section>     
@stop

@section('pageBottom')
@stop