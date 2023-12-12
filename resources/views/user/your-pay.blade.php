<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Your Pay')

@section('content')


<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
        		{!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
    	        {!! breadcrumb('Pay') !!}
        </ol> 
    </div>
        	<h1 class="hero-heading h2">Your Pay Information</h1>
        	
        	<div class="row">
 
            <div class="col-md-6">
            
                <p>{!! nl2br($payDetails) !!}</p>
                
                <h3>Past Payments</h3>
                
                @forelse ($pastPayments as $pastPayment)
                    <p>{!! $pastPayment->eventTime !!} <b>${{{ $pastPayment->subjectString }}}</b> {{{ $pastPayment->data }}}</p>
                @empty
                    <p>None.</p>
                @endforelse
            
            </div>

            <div class="col-md-6">
            
                <div class="well">Your total earnings will be transferred to your PayPal account automatically at the beginning of the month if your amount due is at least ${!! \App\Services\Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}.  If you haven't yet reached ${!! \App\Services\Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}, your balance will continue to accrue each month until you reach that amount.  If you haven't yet reached that amount and you don't plan to continue earning money through Hostelz.com, contact us and we can send your payment for the balance of your current earnings.</div>
                
                <div class="well">
                    <h3>Payment Email Address</h3>
                    
                    <p>Your PayPal payments will be sent to <strong>{{{ $paymentEmail }}}</strong>.</p>
                    
                    <p>If you would like to change the email address that is used for your PayPal payments, you may enter a different email address in the form below.</p>
                
                    <form method="post">
                        <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                        
                        @if (@$status == 'success')
                            <div class="alert alert-success">
                                <p><strong>Your future payments will now be sent to "{{{ $paymentEmail }}}".</strong></p>
                            </div>
                        @else
                            <div class="form-group">
                                <label for="paymentEmail">Email Address for Payments</label>
                                <input class="form-control" id="paymentEmail" name="paymentEmail" value="{{{ @$paymentEmail }}}" autofocus>
                                @if (@$errors) <div class="text-danger bold italic">{!! $errors->first('paymentEmail') !!}</div> @endif
                            </div>
                            <button class="btn btn-primary" type="submit">@langGet('global.Submit')</button>
                        @endif
                    </form>
                </div>
            </div>        

        </div>
</div>
@stop