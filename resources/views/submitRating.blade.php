@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', langGet('submitRating.submitRatingTitle', [ 'hostelName' => $listing->name ]) . ' - Hostelz.com')

@section('header')
    @isset($captcha) {!! $captcha->headerOutput() !!} @endif
@stop

@section('content')

<section class="container mt-5 mt-lg-6">
    <div class="row">
        <div class="col-12">
        	<!-- Breadcrumbs -->
        	<ul class="breadcrumb black px-0">
            	@breadcrumb(langGet('global.Home'), routeURL('home'))
            	@breadcrumb($listing->name, $listing->getURL())
            	@breadcrumb(langGet('submitRating.submitRatingTitle', [ 'hostelName' => $listing->name ]))
        	</ul>

        	<h1 class="hero-heading">@langGet($formHandler->mode == 'updateForm' || $formHandler->mode == 'updateForm' ? 'submitRating.VerifyCommentAbout' : 'submitRating.submitRatingTitle', [ 'hostelName' => '<strong>'.$listing->name.'</strong>' ])</h1>
			
			@if ($formHandler->mode == 'insertForm' || $formHandler->mode == 'updateForm')
                        
            	@if ($formHandler->mode == 'insertForm')
                	<p>@langGet('submitRating.DescribeYourOpinion', [ 'hostelName' => $listing->name ])</p>
                	<p>@langGet('submitRating.NotForQuestions', [ 'hostelName' => $listing->name ])</p>
            	@endif

                <div class="col-lg-12 d-flex comment-form comment-form-guest">
                    @if (auth()->check() && isset($userAvatar))
                        <div class="col col-lg-1 mr-4 p-0 d-flex justify-content-center comment-form-avatar">
                            <img class="sticky-elem avatar mr-2" src="{{ $userAvatar }}" alt="user profile avatar">
                        </div>
                    @endif

                    <form method="post" @class(['col-lg-11' => auth()->check(), 'formHandlerForm', 'form-horizontal', 'mt-3'])>
                        {!! csrf_field() !!}

                        @include('Lib/formHandler/form', [ 'horizontalForm' => false ])

                        <div class="row">
                            <div class="col-md-9 col-md-push-5">

                                @if (isset($formHandler->fieldInfo['email']))
                                    <p>@langGet('submitRating.EnterValidEmailAddress')</p>
                                @endif

                                @isset($captcha) {!! $captcha->formOutput() !!} @endif

                                <button class="btn btn-primary submit my-4" name="mode"
                                        @if ($formHandler->mode == 'insertForm') value="insert" @else value="update" @endif
                                        type="submit">@langGet($formHandler->mode == 'updateForm' ? 'global.Verify' : 'global.Submit')</button>
                            </div>
                        </div>
                    </form>

                    @guest
                        <div class="col-lg-4 sticky-top mb-6 js-show-if-not-login mt-3" style="top: 50px;">
                            @include('articles.sidebar/signupsidebar')
                        </div>
                    @endguest
                </div>
                @if (@$message != '')
                	<div class="alert alert-warning">{!! $message !!}</div>
            	@endif
                <div class="alert alert-info">@include('partials.svg-icon', ['svg_id' => 'info', 'svg_w' => '24', 'svg_h' => '24']) @langGet('submitRating.commentDisclaimer')</div>
            
        	@elseif ($formHandler->mode == 'insert' || $formHandler->mode == 'update')
            
            <div class="alert alert-success">
                @if ($formHandler->model->emailVerified)
                    <strong><i class="fa fa-check-circle"></i> &nbsp;@langGet('submitRating.Thanks')</strong>
                @else
                    <p><strong><i class="fa fa-check-circle"></i> &nbsp;@langGet('submitRating.sendingConfirmationEmail', [ 'email' => $formHandler->model->email ])</strong></p>
                    <p>@langGet('submitRating.checkYourSpam')</p>
                @endif
                
                @if (!$formHandler->model->userID)
                    <p><strong>You can earn points for having submitted this review!</strong> Just <a href="@routeURL('userSignup')" class="underline">sign-up for an account now</a> using the same email address.</p>
                @endif
            </div>
            <p><a href="{!! $listing->getURL() !!}">@langGet('submitRating.ReturnTo', [ 'hostelName' => $listing->name ])</a></p>
        
        @else 
        
            {{-- Mainly in case of error... --}}
            @include('Lib/formHandler/doEverything')
            
        @endif
        		
    	</div>
	</div>
</section>
@stop