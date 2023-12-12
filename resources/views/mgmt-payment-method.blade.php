@extends('layouts/admin')

@section('title', "Listing Management - Payment Method")

@section('header')
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Listing Management', routeURL('mgmt:menu')) !!}
            {!! breadcrumb('Payment Method') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <h2>Payment Method</h2>
        
        <p>Use this form to add or remove your payment method. Your credit card won't be charged until you choose to start a premium listing subscription.</p>
                
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

    </div>

@stop

@section('pageBottom')

@endsection
