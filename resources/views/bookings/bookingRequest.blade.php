<?php 
    $cityInfo = $listing->cityInfo;
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', htmlspecialchars($listing->name) . ' ' . langGet('bookingProcess.Booking') . ' - Hostelz.com')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            @if ($cityInfo)
                {!! breadcrumb($cityInfo->translation()->continent, $cityInfo->getContinentURL(), 'hidden-xs') !!}
                {!! breadcrumb($cityInfo->translation()->country, $cityInfo->getCountryURL()) !!}
                @if ($cityInfo->displaysRegion && $cityInfo->translation()->region != '')
                    {!! breadcrumb($cityInfo->translation()->region, $cityInfo->getRegionURL()) !!}
                @endif
                {!! breadcrumb($cityInfo->translation()->city, $cityInfo->getURL()) !!}
            @endif
            {!! breadcrumb($listing->name, $listing->getURL()) !!}
            {!! breadcrumb(langGet('bookingProcess.Booking')) !!}            
        </ol>
    </div>
    
    <div class="container" id="bookingRequestContent">
    
        @if (@$errorCode != '' || @$errorText != '')
            @include('booking/bookingRequestContent')
        @endif

    </div>
    
@stop


@section('pageBottom')

    <script>
    
        function fetchBookingRequestContent(url, postData) 
        {
            setHtmlToBigWaitSpinner('#bookingRequestContent');
            
            $.ajax({
                url : url,
                dataType: 'html',
                type : (postData == null ? 'GET' : 'POST'),
                data :  postData, 
                timeout: 30000, // (miliseconds)
                tryCount : 0,
                retryLimit : 3,
                success : function(htmlContent) {
                    $('#bookingRequestContent').html(htmlContent);
                },
                error : function(xhr, textStatus, errorThrown ) {
                    if (textStatus == 'timeout') {
                        if (++this.tryCount <= this.retryLimit) {
                            alert('TODO: retrying (this should display a notice on the page)');
                            $.ajax(this); // try again
                            return;
                        }            
                        return;
                    }
                    bookingGeneralErrorMessage('#bookingRequestContent');
                }
            });
        }
        
        @if (@$errorCode == '' || @$errorText == '')
        
            $(document).ready(function() {
                fetchBookingRequestContent(window.location.href.replace('@routeURL('bookingRequest', '', 'relative')', '@routeURL('bookingRequestInnerContent', '', 'relative')'), null);
            });
        
        @endif
    
    </script>

@stop
