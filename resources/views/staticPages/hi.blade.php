<?php

Lib\HttpAsset::requireAsset('booking-main.js');

?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', 'Hostelling International - Hostelz.com')

@section('content')
	<section class="pt-3 pb-5 container">
		<div class="row">
			<div class="col-12">
				<!-- Breadcrumbs -->
				<ul class="breadcrumb black text-dark px-0 mx-sm-n3 mx-lg-0">
					{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
					{!! breadcrumb('Hostelling International (HI)') !!}
				</ul>

				<h1 class="hero-heading">Hostelling International (HI)</h1>

				<p class="text-center"><img src="{!! routeURL('images', 'hiBigLogo.gif') !!}"></p>

				<h2>@langGet('hi.WhatIsHI')</h2>
				<p>@langGet('hi.DescriptionOfHI')</p>

				<h2>@langGet('hi.BecomeAMemberQuestion')</h2>
				<p>@langGet('hi.BecomeAMemberAnswer')</p>

				{{-- <h2 class=textCenter>@langGet('hi.GetACard')</h2>
				<ul>
					<li><h3 class="textCenter md2Color"><a href="{!! routeURL('hi-usa') !!}">@langGet('hi.USResidents')</a></h3></li>
					<h2 class="textCenter md2Color"><a href="">@langGet('hi.UKResidents')</a></h2>
					<li><h3 class="textCenter md2Color"><a href="https://www.hihostels.com/info/membership" target=_blank>@langGet('hi.OtherResidents')</a></h3></li>
				</ul>--}}

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
