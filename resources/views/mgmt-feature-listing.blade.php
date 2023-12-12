<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?> 

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => false ])

@section('title', "Listing Management - Featured Listing")

@section('header')
    @parent
@stop

@section('content')
<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Listing Management', routeURL('mgmt:menu')) !!}
            {!! breadcrumb('Featured Listing') !!}
        </ol>
    </div>
    
        <h1 class="hero-heading h2">Featured Listing</h1>
        <div class="pb-5"> 
    
        <p>We'll soon give you the option to have a "featured listing".</p>
        <p>It will help make your hostel more prominent on the site to help your host get more attention and 
            generate more bookings. Featured listings will appear at the top of the hostels list for the city, 
            and they'll be highlighted to attract as much attention as possible. The pricing hasn't yet been set.
        </p>
                
        @if ($optedIn || $hasNotified)
        
            <div class="row">
                <div class="col-md-6">
                    <div class="alert alert-success"><i class="fa fa-check-circle"></i> Great, we'll let you know as soon as featured listings are available for your city</div>
                </div>
            </div>
        
        @else
            <h3>Do you want to get notified when featured listings are available?</h3>
            <div class="py-3">
                <form method="post"> 
                    {{ csrf_field() }}
                    <div class="checkbox mb-3">
                        <label>
                            <input type="checkbox" name="optIn" value="true"> Yes, please let me know when featured listings are available.
                        </label>
                    </div>    
                    <button class="btn btn-lg btn-primary" type="submit">Submit</button>
                </form>
            </div>
        @endif
        
    
    {{-- (This stuff was copied from the payment page code from BlueCheck -- may be a useful starting point)
    
    
        <h2>Payment Method</h2>
        
        <p>Use this form to add or remove your payment method.  Your credit card won't be charged until you choose to start a premium listing subscription.</p>
        
        <br>
        
        @if ($submitStatus == 'deleted')
            <div class="alert alert-success">
                <p><i class="fa fa-check-circle"></i> &nbsp;Your payment method has been removed.</p>
            </div>
        @elseif ($submitStatus == 'success')
            <div class="alert alert-success">
                <p><i class="fa fa-check-circle"></i> &nbsp;Your payment method has been successfully added.</p>
            </div>
        @elseif ($submitStatus == 'reactivated')
            <div class="alert alert-success">
                <p><i class="fa fa-check-circle"></i> &nbsp;Your payment method has been successfully reactivated.</p>
            </div>
        @endif
    
        @if ($paymentMethod)
        
            <div class="row">
            
                <div class="col-md-6">

                    <div class="panel panel-info">
                        <div class="panel-heading">
                            {{{ $paymentMethod->getDisplayName(true) }}}
                        </div>
                        <div class="panel-body">
                            <p>{{{ $paymentMethod->getDisplayDetails() }}}</p>
        
                            @if ($paymentMethod->status == 'deactivated')
                                <form method="post">
                                    {{ csrf_field() }}
                                    <button class="btn btn-sm btn-primary" type="submit" name="reactivate" value="true">Re-activate This Card</button>
                                </form>
                                <br>
                            @endif
        
                            <form method="post">
                                {{ csrf_field() }}
                                <button class="btn btn-sm btn-danger" type="submit" name="delete" value="true" 
                                    onclick="return confirm('Are you sure you want to delete this card?');">Delete This Card</button>
                            </form>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        
        @else
        
            <div class="row">
            
                <div class="col-md-6">

                
                    @if ($errorMessage != '') 
                        <p class="text-danger"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> {{ $errorMessage }}</p> 
                    @endif
        
                    @include('paymentProcessor::Stripe/views/addNew')
                
                </div>
            
            </div>
                
        @endif

    --}}
        </div>
</div>

@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop
