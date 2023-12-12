@extends('layouts/admin')

@section('title', 'Pay All Users')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Pay All Users') !!}</li>
        </ol>
    </div>
    
    <div class="container">
    
        <h3>Payments</h3>
        
        @if ($payments)
                        
            <form method="post" class="form-inline">
                {!! csrf_field() !!}
                <input type="password" class="form-control" name="paypalPassword" size=20 placeholder="PayPal Password">
                <button class="btn btn-primary" type="submit">Pay Now</button>
            </form>
    
            <br>
            
            @foreach ($payments as $payment) 
                <p>
                    <a href="{!! routeURL('staff-users', [ $payment['user']->id ]) !!}">{{{ $payment['user']->name }}} &lt;{{{ $payment['user']->username }}}&gt;</a> 
                    ({!! implode(', ', $payment['user']->access) !!})
                    ${!! $payment['amountDue'] !!} 
                    @if ($payment['lastPaid']) due since last payment on {!! $payment['lastPaid'] !!} @endif
                    @if (@$payment['status'] == 'payment error')
                        <strong style="color:red"><i class="fa fa-exclamation-circle"></i> Payment Error</strong>
                    @elseif (@$payment['status'] == 'payment sent')
                        <strong style="color:green">Paid</strong>
                    @endif
                </p>
            @endforeach
            
        @else
        
            <br>
            <p>No payments need to be made at this time.</p>
        
        @endif
    
        @if ($paymentSystemBalance !== null)
            <br>
            <p><strong>Payment System Balance: ${{{ $paymentSystemBalance }}}</strong></p>
        @endif
        
    </div>

@stop
