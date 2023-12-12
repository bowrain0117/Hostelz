<?php

Lib\HttpAsset::requireAsset('booking-main.js');

?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('global.Partner').' - Hostelz.com')

@section('header')
@stop

@section('content')
	<section class="mb-5">
		<!--  Breadcrumbs  -->
		<div class="container">
			<ul class="breadcrumb black px-0 mx-sm-n3 mx-lg-0">
				{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
				{!! breadcrumb(langGet('global.Partner')) !!}
			</ul>

			<h1 class="mb-3 mb-lg-4 pb-md-2">@langGet('global.Partner')</h1>
			<p>We teamed up with several partners.</p>
		</div>
	</section>

	<section class="container">
		<div class="row flex-column flex-lg-row mb-md-4 mt-md-2 pb-2">
			<div class="col-lg-3">
				<div class="p-3 p-sm-4 ml-sm-n3 mr-sm-n3 mr-lg-0 mb-4">
					<img class="w-100" src="{!! routeURL('images', 'partner-skyscanner.jpg') !!}">
				</div>
			</div>
			<div class="col-lg-9">
				<div class="p-3 p-sm-4 ml-sm-n3 mr-sm-n3 mr-lg-0 mb-4">
					<h2 class="font-weight-600 mb-2 mb-lg-3"><a href="#" target="_blank" class="listing__title text-decoration-none text-dark" title="">Skyscanner</a></h2>
					<p class="mb-3"><a href="#">Skyscanner</a> is for flights what Hostelz.com is for hostels. This website is a complete search price comparison tool to fnd the cheapest flights around the globe.</p>
				</div>
			</div>
		</div>

		<div class="row flex-column flex-lg-row mb-md-4 mt-md-2 pb-2">
			<div class="col-lg-3">
				<div class="p-3 p-sm-4 ml-sm-n3 mr-sm-n3 mr-lg-0 mb-4">
					<img class="w-100" src="{!! routeURL('images', 'partner-getyourguidelogo.jpg') !!}">
				</div>
			</div>
			<div class="col-lg-9">
				<div class="p-3 p-sm-4 ml-sm-n3 mr-sm-n3 mr-lg-0 mb-4">
					<h2 class="font-weight-600 mb-2 mb-lg-3"><a href="#" target="_blank" class="listing__title text-decoration-none text-dark" title="">Get Your Guide</a></h2>
					<p class="mb-3">Get Your Guide is one of the leading platforms<a href="#"> for tickets and sightseeing</a>.</p>
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
