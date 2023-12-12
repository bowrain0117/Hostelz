@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', langGet('city.TravelTipsTitle', [ 'city' => $cityInfo->translation()->city ]) . ' - Hostelz.com')

@section('header')
    @if (@$captcha) {!! $captcha->headerOutput() !!} @endif
@stop

@section('content')

<section class="pt-3 pb-5 container">
	<div class="row">
        <div class="col-12">
          		<!-- Breadcrumbs -->
        		<ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0">
            		@breadcrumb(langGet('global.Home'), routeURL('home'))
            		@breadcrumb($cityInfo->translation()->city, $cityInfo->getURL())
            		@breadcrumb(langGet('city.TravelTipsTitle', [ 'city' => $cityInfo->translation()->city ]))
        		</ul>
        		<h1 class="hero-heading">@langGet('city.TravelTipsTitle', [ 'city' => $cityInfo->translation()->city ])</h1>
        		@if ($formHandler->mode == 'insertForm')
        
        		<div class="well">@langGet('city.CityCommentsOnly', [ 'city' => $cityInfo->translation()->city ])</div>        
            	{{-- Error Messages --}}
            	@if (@$message != '')
                	<div class="well">{!! $message !!}</div>
            	@endif
            
            	<form method="post" class="formHandlerForm form-horizontal">
                	{!! csrf_field() !!}
                	@include('Lib/formHandler/form', [ 'horizontalForm' => true ])
                
                	<div class="row">
                    	<div class="col-md-9 col-md-push-3">
                        @if (@$captcha) {!! $captcha->formOutput() !!} @endif
                        <button class="btn btn-primary submit my-4" name="mode" value="insert" type="submit">@langGet('global.Submit')</button>
                   		</div>
                	</div>
            	</form>
            
        		@elseif ($formHandler->mode == 'insert' || $fakeInsert)
                    
            	<div class="alert alert-success">
                	<h3><i class="fa fa-check-circle"></i> &nbsp;@langGet('city.CommentPosted')</h3>
                	<p>@langGet('city.CityCommentReturnText')</p>
                	<p><a href="{!! $cityInfo->getURL() !!}">@langGet('city.ReturnToCity', [ 'city' => $cityInfo->translation()->city ])</a></p>
            	</div>
        
        		@else 
            		{{-- Mainly in case of error... --}}
            		@include(qf-genericModeResponse.inc.html)
        		@endif 
        </div>
    </div>
</section>
@stop