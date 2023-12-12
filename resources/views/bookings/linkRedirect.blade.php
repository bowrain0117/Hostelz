{{-- The adWordsConversionTrackingCode came from an AdWords "conversion action" that we set up to track booking page clicks. --}}
@extends('layouts/forward', [ 'customHeader' => '', 'customFooter' => '', 'adWordsConversionTrackingCode' => 'AW-1072053899/Y8PsCMruqH8Qi_2Y_wM' ])

@section('title', 'Hostelz.com - Connecting to ' . $importSystemName)

@section('header')
    
{{--    <meta http-equiv="refresh" content="0;URL={{{ $redirectURL }}}">--}}

    <script type="text/javascript">
      var redirectURL = "{!! $redirectURL !!}";
      var storageOption = 'hostelzBookingRedirect:lastVisitTime';
      var lastVisitTime = localStorage.getItem(storageOption);
      var delay = 5000; // default delay in milliseconds
      var minVisitDelay = 24 * 60 * 60 * 1000; // 24 hours

      if (!lastVisitTime || ((Date.now() - lastVisitTime) > minVisitDelay)) {
        localStorage.setItem(storageOption, Date.now());
      } else {
        delay = 1000;
      }

      setTimeout(function (){
        window.location = redirectURL;
      }, delay)
    </script>
    
    <meta name="robots" content="noindex, nofollow">

@stop

@section('content')

<section class="container mt-6 pt-4">
	<div class="row">
	  
	  <div class="col-lg-12">
		<div class="card mb-5 mb-lg-0 border-0 card-highlight shadow-lg">
		  
		  <div class="card-body">
        <p class="text-center">
          <img src="{!! routeURL('images', 'logo-hostelz-big.png') !!}" alt="Hostelz.com - hostel price comparison" title="Hostelz.com - hostel price comparison" style="max-width: 200px;" class="mb-3 mb-sm-5">
        </p>
        <p class="text-center"><span class="sb-title cl-text">@langGet('bookingProcess.linkRedirect.Connection', ['name' => $importSystemName])</span></p>
        <p class="text-center"><img src="/images/loading.svg" class="b-3 mb-sm-4 spinner-border border-0"></p>
        <hr>
        <p class="mt-5 h3 text-center alert alert-warning">Please finalize your reservation</p>
        <p class="display-3 text-center">Remember to come back to compare hostels and find the best price.</p>
		  </div>
		</div>
	  </div>
	  
  </div>
 
</section>



@stop

