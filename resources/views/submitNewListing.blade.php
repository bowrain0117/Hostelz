<?php
    Lib\HttpAsset::requireAsset('autocomplete');
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('submitNewListing.SubmitListing') . ' - Hostelz.com')

@section('header')
    @if (@$captcha) {!! $captcha->headerOutput() !!} @endif
@stop

@section('content')
<section>
    <div class="container">
        <div class="col-12 mb-lg-6 mb-6 px-0">
    <!--  Breadcrumbs  -->

        	<ul class="breadcrumb black px-0 mx-lg-0">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('submitNewListing.SubmitListing')) !!}
            </ul>
            <h1 class="mb-3 mb-lg-5 pb-md-2">@langGet('submitNewListing.SubmitListing')</h1>


    @if ($formHandler->mode == 'insertForm')
 
        <!-- SubmitListingInfo-->
            <div class="alert alert-info" role="alert">
                <p class="">
                    @langGet('submitNewListing.SubmitListingInfo')
                </p>
                <p class=""><b>Good to know:</b> Did you know you can claim your listing on Hostelz.com? Quickly update your listing with the latest information, add your official website, and much more. You can easily increase your sales with Hostelz.com by claiming your listing. <a href="@routeURL('contact-us', [ 'contact-form', 'listings'])" title="Contact Hostelz">Get in touch</a>. Please use the official hostel email address for the verification process.</p>
                <div class="py-3 text-center">
                    <button class="btn-lg btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>	
                </div>
                <p class="pt-4"><b>Your hostel is indeed not yet listed at Hostelz.com?</b> Then let's get started.</p>
            </div>

        {{-- Error Messages --}}
        @if (@$message != '')
            <div class="well">{!! $message !!}</div>
        @endif


        <section class="shadow-1 rounded p-4 p-md-5 mb-4 mb-md-7">
            <form method="post" class="formHandlerForm form-horizontal">
                {!! csrf_field() !!}
                @include('Lib/formHandler/form')
                @if (@$captcha) {!! $captcha->formOutput() !!} @endif
				
				<div class="my-4">
                <button type="submit" class="btn btn-primary d-flex m-auto px-sm-5 font-weight-600" name="mode" value="insert">
                    <img src="{!! routeURL('images', 'house.svg') !!}" alt="Sign Up" title="Sign Up" class="mr-2">
                    @langGet('global.NewListing')
                </button>
				</div>
            </form>
        </section>

    @elseif ($formHandler->mode == 'insert')

        <section class="shadow-1 rounded p-4 p-md-5 mb-4 mb-md-7">
            <div class="alert alert-success">
                <h2><span class="glyphicon glyphicon-ok"></span> @langGet('submitNewListing.SubmitListingReturnTitle')</h2>
                <p>{!! langGet('submitNewListing.SubmitListingReturnText', [ 'linkToUsURL' => routeURL('linkToUs') ]) !!}</p>
            </div>
        </section>
    @endif
        </div>
    </div>
</section>

@stop
    
@section('pageBottom')
    @if ($formHandler->mode == 'insertForm')
        <script>
            $(document).ready(function() 
            {
                {{-- Autocomplete Country --}}
                $('.formHandlerForm input[name="data[country]"]').devbridgeAutocomplete({
                    serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                    paramName: 'search',
                    params: {
                        'field': 'country'
                    },
                    minChars: 1,
                    deferRequestBy: 100 {{-- wait briefly to see if they hit another character before querying --}}
                });
                
                {{-- Autocomplete City --}}
                $('.formHandlerForm input[name="data[city]"]').devbridgeAutocomplete({
                    serviceUrl: '{!! routeURL('addressAutocomplete') !!}',
                    paramName: 'search',
                    params: {
                        'field': 'city',
                        'context[country]': function () { return $('.formHandlerForm [name="data[country]"]').val(); },
                    },
                    minChars: 1,
                    deferRequestBy: 100 {{-- wait briefly to see if they hit another character before querying --}}
                });
                
                {{-- 
                    (temp?) fix of issue where autocomplete causes the browser not to remember values the user entered when they go back to the form 
                    See https://github.com/devbridge/jQuery-Autocomplete/issues/393.
                --}}
                $(window).bind('beforeunload', function() {
                    $('.formHandlerForm input').removeAttr( "autocomplete" );
                }); 
            });
        </script>
    @endif
    @parent
    <script>
		initializeTopHeaderSearch();
	</script>
@stop