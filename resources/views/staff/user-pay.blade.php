@extends('layouts/admin')

@section('title', 'User Pay')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('User', routeURL('staff-users', [ $user->id ])) !!}
            {!! breadcrumb('Pay') !!}
        </ol>
    </div>
    
    <div class="container">
    
        @if ($paymentSystemBalance !== null)
            <h3>Payment System Balance: ${{{ $paymentSystemBalance }}}</h3>
        @endif
    
        @if ($status == 'payment sent')
        
            <br>
            <div class="row">
                <div class="col-md-6">
                    <div class="alert alert-success">Payment sent successfully.</div>
                </div>
            </div>
            
        @else
    
            <h3>Current Balance</h3>
            
            <p>{!! nl2br($payDetails) !!}</p>
            
            @if ($amountDue)
                @if ($status == 'payment error')
                    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>
                        Payment error (wrong password?).
                    </div>
                @endif
                
                <form method=post class="form-inline">
                    {!! csrf_field() !!}
                    <input type="password" class="form-control" name="paypalPassword" size=20 placeholder="PayPal Password">
                    <button class="btn btn-primary" type="submit">Pay Now</button>
                </form>
            @endif
            
            <h3>Past Payments</h3>
            
            @forelse ($pastPayments as $pastPayment)
                <p>{!! $pastPayment->eventTime !!} <b>${{{ $pastPayment->subjectString }}}</b> {{{ $pastPayment->data }}}</p>
            @empty
                <p>None.</p>
            @endforelse
        
        @endif
        
    </div>

@stop
