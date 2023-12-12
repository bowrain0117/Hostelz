<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', ['showHeaderSearch' => true ])

@section('title', 'Maintenance in progress - Hostelz.com')

@section('content')
	<section>
		<div class="container">
			<div class="col-12 mb-lg-6 mb-6 px-0">

				<ul class="breadcrumb black px-0 mx-lg-0">
					{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
					{!! breadcrumb('On Hold') !!}
				</ul>

				<h1 class="mb-3 mb-lg-5 pb-md-2">This site is currently on a well-deserved vacation!</h1>

                <p class="">We are doing some <b>maintenance work</b> here, please excuse the inconvenience!</p>
                <p class="">In the meantime, please use our powerful hostel search engine. It will bring you to any destination you want.</p>
                <div class="">
                    <button class="btn btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>Search</button>
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