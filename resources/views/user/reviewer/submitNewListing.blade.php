<?php 
    Lib\HttpAsset::requireAsset('autocomplete');
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])


@section('title', 'Reviewer - Hostelz.com')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('My Reviews', routeURL('reviewer:reviews')) !!}
            {!! breadcrumb(langGet('submitNewListing.SubmitListing')) !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="row">
            <div class="col-md-8">
                @if ($formHandler->mode == 'insertForm')
                
                    <h1 class="hero-heading h2">@langGet('submitNewListing.SubmitListing')</h1>
        
                    <div class="alert alert-info">
                        PLEASE MAKE SURE THIS HOSTEL ISN'T ALREADY LISTED ON OUR SITE BEFORE YOU ADD IT. Make sure you've searched for all possible spellings of the name and you've also searched by the hostel's city to see if it's listed there.
                    </div>
                    <div class="alert alert-info">
                        <p>Be sure it's really a primarily a hostel!  We can't accept reviews for new listings unless they are primarily a hostel.  That means they must offer dorm beds, and those dorm room beds must be advertised on their website.  Also, hotels or guest houses that aren't already live on our site that only have one dorm room but are otherwise mostly a hotel probably won't qualify as being primarily a hostel. If you are unsure, you may want to contact us first before writing a review of a listing that isn't already live on our site. </p>
                        <p>New hostels added to our site must have their own website (their Facebook page is acceptable if that is all they have, but a third party booking website is not). Hostels without a website can not be added at this time.</p>
                    </div>
                    <br>
                    
                    {{-- Error Messages --}}
                    
                    @if (@$message != '')
                        <br><div class="well">{!! $message !!}</div>
                    @endif
                    
                    <form method="post" class="formHandlerForm form-horizontal">
                        {!! csrf_field() !!}
                        @include('Lib/formHandler/form', [ 'horizontalForm' => true ])
                        
                        <br>
                        
                        <div class="well">
                            It's important that we have all of the contact information for the hostel. If you don't have the website or email address for a hostel, ask them for this info during or before your stay and include it as a listing update when you submit your review. We can't add a new hostel without an email address and usually a website.
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-9 col-md-push-3">
                                <button class="btn btn-primary submit" name="mode" value="insert" type="submit">@langGet('global.Submit')</button>
                            </div>
                        </div>
                    </form>
                    
                @endif
            
            </div>
        </div>
    </div>
    
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
    
@stop
