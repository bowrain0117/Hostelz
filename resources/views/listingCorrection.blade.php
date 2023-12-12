@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', langGet('listingCorrection.ListingCorrection') . ' - Hostelz.com')

@section('header')
    @if (@$captcha) {!! $captcha->headerOutput() !!} @endif
@stop

@section('content')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb($listing->name, $listing->getURL()) !!}
            {!! breadcrumb(langGet('listingCorrection.ListingCorrection')) !!}
        </ol>  
    </div> 
    <div class="mb-lg-2 pb-md-2 mx-sm-n3 mx-lg-0">
        <h1 class="mb-2 mb-lg-0 h2">@langGet('listingCorrection.ListingCorrection')</h1>
    </div>    
    <div class=""> 

        @if ($formHandler->mode == 'insertForm')
        
        	<div class="alert alert-warning">@langGet('listingCorrection.ThisFormIs')</div>
            <div class="mb-3 bg-light p-3 rounded">
                <h3> @langGet('listingCorrection.OwnerContactUsTitle', ['hostelName' => $listing->name,  'city' => $listing->city]) <span class="badge badge-primary">@langGet('global.New')</span></h3>
                <p>@langGet('listingCorrection.OwnerContactUs', ['contactlink' => routeURL('contact-us')])</p>
            </div>

            <h2>{{{ $listing->name }}}, {{{ $listing->city }}}</h2>

            {{-- Error Messages --}}
            
            @if (@$message != '')
                <div class="well">{!! $message !!}</div>
            @endif
                        
            <form method="post" class="formHandlerForm form-horizontal">
                {!! csrf_field() !!}
                @include('Lib/formHandler/form', [ 'horizontalForm' => false ])
                <div class="row">
                    <div class="col-md-9 col-md-push-3 mb-3">
                        @if (@$captcha) {!! $captcha->formOutput() !!} @endif
                        <button class="btn btn-primary submit mt-3" name="mode" value="insert" type="submit">@langGet('global.Submit')</button>
                    </div>
                </div>
            </form>    
            <br>
        	<div class="alert alert-warning">@langGet('listingCorrection.ThisFormIs')</div>

        @elseif ($formHandler->mode == 'insert' || $fakeInsert)
            <div class="alert alert-success"> 
                <h3><i class="fa fa-check-circle"></i> @langGet('listingCorrection.ListingCorrectionReturnTitle')</h3>
                <p>@langGet('listingCorrection.ListingCorrectionReturnText')</p>
                <p><a href="{!! $listing->getURL() !!}">@langGet('listingCorrection.Return')</a></p>
            </div>
        
        @else 
            {{-- Mainly in case of error... --}}
            @include(qf-genericModeResponse.inc.html)
        @endif 
        
    </div> 
</div>
@stop