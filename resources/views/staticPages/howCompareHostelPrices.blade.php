<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.HowItWorksMetaTitle', ['year' => date("Y")]))

@section('header')
    <meta name="description" content="{!! langGet('SeoInfo.HowItWorksMetaDescription', ['year' => date("Y")]) !!}">
	<meta property="og:title" content="{!! langGet('SeoInfo.HowItWorksMetaTitle', ['year' => date("Y")]) !!}" />
    <meta property="og:description" content="{!! langGet('SeoInfo.HowItWorksMetaDescription', ['year' => date("Y")]) !!}" />
@stop

@section('content')
	<section class="pb-5 pb-sm-6 pb-md-7 pt-5 pt-md-6">
		<div class="container">
			<div class="col-12 mb-md-6 mb-4 px-0">
				<p class="sb-title text-primary mb-2 text-left text-lg-center">The backpacker's best friend</p>
				<h1 class="h2 cl-dark mb-0 text-left text-lg-center">How does Hostelz.com work?</h1>
			</div>
			<div class="row align-items-center justify-content-center">

				<div class="col-md-6 position-relative mb-3">
					<p class="mb-4">Let's say, you want to stay at this super cool hostel in Bali.</p>
					<h2 class="">Which of the 3 platforms would you use for your reservation?</h2>
				</div>

				<div class="col-md-6 position-relative dark-overlay card">
					<img alt="How does Hostelz.com work?" src="{!! routeURL('images', 'how-compare-hostel-prices-hostelz.jpg') !!}" class="bg-image">
			
 					<div class="overlay-content">
						<div class="row  align-items-center justify-content-center py-7 text-center">
							<div class="col-lg-4 mb-2 mb-md-0">
								<span class="hover-animate card mb-3 mb-lg-0 border-0 shadow-lg">
									<div class="card-body">
										<p class="text-center h2 text-dark"><span>$12</span><span class="font-weight-normal text-xs">/ night</span></p>
										<hr>
										<h5>Platform A</h5>
									</div>
								</span>
							</div>

							<div class="col-lg-4 mb-2 mb-md-0">
								<span class="hover-animate card mb-3 mb-lg-0 border-0 shadow-lg card-md-highlight">
									<a href="#savemoney" class="text-decoration-none" data-smooth-scroll=""><div class="card-body">
										<p class="h2 text-dark"><span>$9</span><span class="font-weight-normal text-xs">/ night</span></p>
										<hr>
										<h5>Platform B</h5>
										<span class="btn btn-danger rounded-sm"><span class="tx-small text-lowercase">you save</span> <span class="font-weight-600">25%</span></span>
									</div>
									</a>
								</span>
							</div>

							<div class="col-lg-4 mb-2 mb-md-0">
								<span class="hover-animate card mb-3 mb-lg-0 border-0 shadow-lg">
									<div class="card-body">
										<p class="h2 text-dark"><span>$14</span><span class="font-weight-normal text-xs">/ night</span></p>
										<hr>
										<h5>Platform C</h5>
									</div>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="pb-4 pb-sm-6 pb-md-6 pt-4 pt-md-5 bg-gray-100">
		<div class="container">
			<div class="text-center pt-5 pt-md-7 mt-4 mt-md-5 justify-content-around" id="savemoney">

				<div class="col-lg-12 pb-3 pb-md-5">
                    <p class="">Quite obviously, you will choose the cheapest price for the accommodation!</p>

                    <p class="mb-3">In our example above, <span class="btn btn-danger rounded-sm"><span class="tx-small text-lowercase">you just saved a whopping </span><span class="font-weight-600">25%</span></span></p>        
					<h2 class="font-weight-bold mb-3">This is why you will love <svg width="104" height="24"><use xlink:href="#hostelz-logo"></use></svg></h2>
				</div>

				<div class="text-center my-2 my-md-4">
					<div class="col-lg-12">

						<p class="mb-3">You can compare prices on all major hostel booking sites with 1 click! We are not a booking portal, but your gateway to simply find the best prices.</p>
						<h3 class="mb-3">You save time and money.</h3>
						<p class="mb-3">On average you save 10.6%, as much as 50%-60% on many bookings depending on the hostel.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="pb-5 pb-sm-6 pb-md-7 pt-5 pt-md-6">
		<div class="container">
			<div class="text-center pt-2 py-md-3 my-4 my-md-5 justify-content-around" id="features">
				<div class="text-center my-2 my-md-5 pt-md-4">
					<h2 class="font-weight-bold mb-5">With Hostelz.com you can...</h2>
					<div class="row justify-content-around">
						<div class="col-12 col-md-3 text-center mb-4 mb-md-0">
							<div class="icon-rounded mb-4 bg-primary">
								<svg class="svg-icon w-2rem h-2rem text-white">
									<use xlink:href="#money-1"></use>
								</svg>
							</div>
							<h3 class="h5 text-md-left">Compare Prices on Booking Sites with 1 Click</h3>
							<p class="text-md-left">We help you find the best prices - you save real money and time!</p>
							<div id="heading1">
								<p id="collapse1" aria-labelledby="heading1" class="collapse mt-2 text-md-left">Every booking portal has different rates and policies, even availability. On Hostelz you get all prices and all availability at a glance.</p>
								<a data-toggle="collapse" href="#collapse1" aria-expanded="false" aria-controls="collapseOne" class="accordion-link cl-text py-0 collapse-arrow-wrap icon-rounded icon-rounded-sm bg-second mx-auto collapsed"><i class="fas fa-angle-down text-white"></i><i class="fas fa-angle-up text-white"></i></a>
                            </div>
						</div>

						<div class="col-12 col-md-3 text-center mb-4 mb-md-0">
							<div class="icon-rounded mb-4 bg-primary">
								<svg class="svg-icon w-2rem h-2rem text-white">
									<use xlink:href="#world-map-1"></use>
								</svg>
							</div>
							<h3 class="h5 text-md-left">Find Every Single Hostel</h3>
							<p class="text-md-left">We are the only website in the world listing all hostels in the world!
							<div id="heading2">
								<p id="collapse2" aria-labelledby="heading2" class="collapse mt-2 text-md-left">In order for you to find a hostel on a booking portal, the hostel has to sign up and add their rooms and dorms to it. If a hostel does not do this, you won't find it. Hostelz.com is the only website in the world listing all hostels worldwide. Even if a hostel is not using a booking website (but has their own website or social media profile), we will list it on Hostelz.com so you can find it.</p>
								<a data-toggle="collapse" href="#collapse2" aria-expanded="false" aria-controls="collapseTwo" class="accordion-link cl-text py-0 collapse-arrow-wrap icon-rounded icon-rounded-sm bg-second mx-auto collapsed"><i class="fas fa-angle-down text-white"></i><i class="fas fa-angle-up text-white"></i></a>
                            </div>
						</div>

						<div class="col-12 col-md-3 text-center mb-4 mb-md-0">
							<div class="icon-rounded mb-4 bg-primary">
								<svg class="svg-icon w-2rem h-2rem text-white">
									<use xlink:href="#speedometer-1"></use>
								</svg>
							</div>
							<h3 class="h5 text-md-left">Exclusive Discounts and Tips</h3>
							<p class="text-md-left">Sign up with hostelz.com and get access to exclusive travel tips and hidden gems. Rumor has it, you will also receive special hostel discounts...</p>

						</div>
					</div>
				</div>
                <div class="pt-4">
                    <button class="btn-lg btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>	
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