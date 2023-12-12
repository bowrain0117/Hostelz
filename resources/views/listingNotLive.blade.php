<?php 
    Lib\HttpAsset::requireAsset('listingDisplay.js'); 
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', htmlspecialchars($listing->name) . ' - Hostelz.com')

@section('content')

<section class="pt-3 pb-5 container">
	<div class="row">
        <div class="col-12">
        		<!-- Breadcrumbs -->
        		<ul class="breadcrumb text-dark px-0 mx-sm-n3 mx-lg-0">
					{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            		@if ($cityInfo)
                		{!! breadcrumb($cityInfo->translation()->continent, $cityInfo->getContinentURL(), 'hidden-xs') !!}
                		{!! breadcrumb($cityInfo->translation()->country, $cityInfo->getCountryURL()) !!}
                		@if ($cityInfo->displaysRegion && $cityInfo->translation()->region != '')
                    		{!! breadcrumb($cityInfo->translation()->region, $cityInfo->getRegionURL()) !!}
                		@endif
                		{!! breadcrumb($cityInfo->translation()->city, $cityInfo->getURL()) !!}
            		@endif
            		{!! breadcrumb($listing->name) !!}
            	</ul>
            	
            	<div class="listingTopButtons">
           			<a href="{!! routeURL('staff-listings', $listing->id) !!}" class="btn btn-sm btn-default" id="editListing"><i class="fa fa-pencil-square-o"></i>&nbsp; Edit Listing</a>
        		</div>
            	
        		<h1 class="hero-heading" property="name">{{{ $listing->name }}}</h1>
        		
        		<div class="well text-center">
            		@include('partials/_listingClosedAlert')
        		</div>
		</div>
	</div>
</section>
@stop
@section('pageBottom')
    @parent
    <script type="text/javascript">
        initializeListingDisplayPage({!! $listing->id !!}, true, {!! $listing->lastUpdatedTimeStamp() !!}, false);
    </script>
@stop