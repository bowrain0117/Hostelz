@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', langGet('SeoInfo.BlogMetaTitle', ['year' => date("Y")] ))

@section('header')
    <link rel="alternate" type="application/rss+xml" title="Hostelz.com {{{ langGet('articles.Articles') }}}" href="{!! routeURL('articles', 'rss') !!}" />
    <meta name="description" content="{!! langGet('SeoInfo.BlogMetaDescription') !!}">
@stop

@section('content')

<!--Open Screen-->
<section class="hero text-white dark-overlay bg-cover hero-blog flex-center mb-3 mb-lg-5">
	<div class="hero text-white dark-overlay bg-cover hero-blog flex-center w-100">
		<img src="/images/articles/exclusive-content.jpg" alt="The juicy insider Tips " class="bg-image">
		<div class="card-img-overlay d-flex align-items-center">
			<div class="w-100 overlay-content container flex-center--column">
				<ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0 text-white" vocab="http://schema.org/" typeof="BreadcrumbList">
    				{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('articles.Articles'), routeURL('articles')) !!}
                {!! breadcrumb('The juicy insider Tips') !!}
    			</ul>
				<h1 class="text-center mb-0 text-white">The juicy insider Tips <a href="{!! routeURL('articles', 'rss') !!}" target="_blank" title="RSS Hostel Blog" class="text-hover-primary"><i class="fas fa-rss-square text-sm"></i></a></h1>
			</div>
		</div>
	</div>
</section>

<section class="container mt-2 mt-lg-2 mb-5">
	<div class="mb-4">
		<h1 class="mb-2 mb-lg-3 pb-md-2 ">Something worthwhile is coming</h1>
		<p>We’re currently putting together some juicy articles, exclusive for Hostelz.com members ONLY! And no, it won’t cost you a penny. Simply sign up for free and be the first to get notified once our exclusive travel guides go live.</p>
		<h3>What can you expect?</h3>
		<p>How does exclusive discounts for hostels, tours, transport and all things backpacker-related sound? We’re on to it. What about insider tips from the community, hostel owners and the Hostelz.com team? Trust us when we say it’s all on the way, and much more.</p>

		<p class="js-show-if-not-login">Let’s start with the easy first step: Sign up for free below!</p>

		<p class="js-show-if-login">You are already signed up and logged in at Hostelz.com, hurray! We will send you a quick email once our exclusive content kicks live.</p>

	</div>
</section>

@stop
