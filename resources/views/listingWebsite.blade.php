@extends('layouts/forward', [ 'customHeader' => '', 'customFooter' => '' ])

@section('title', 'Hostelz.com - Redirecting...')

@section('header')
    <meta http-equiv="refresh" content="5;URL={{{ $url }}}">
    
    {{-- This allows browsers to pass the referrer code even if this page is hosted on https.
        It lets hostels know we're sending them traffic. --}}
    <meta name="referrer" content="unsafe-url">
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
          <p class="text-center"><span class="sb-title cl-text">Redirecting you to {{{ $listing->web }}}</span></p>
          <p class="text-center"><img src="/images/loading.svg" class="b-3 mb-sm-4 spinner-border border-0"></p>
          <hr>
          <p class="mt-5 h3 text-center alert alert-warning">Please use the links we provide for your final reservation</p>
          <p class="display-3 text-center">It is at NO extra cost for you. And you help us to keep Hostelz.com running.</p>
        </div>
      </div>
	  </div>
	  
  </div>
 
</section>
@stop