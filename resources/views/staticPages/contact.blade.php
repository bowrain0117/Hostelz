<?php

Lib\HttpAsset::requireAsset('booking-main.js');

?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.ContactMetaTitle', ['year' => date("Y")]))

@section ('header')
    @if (@$captcha) {!! $captcha->headerOutput() !!} @endif
    <meta name="description" content="{!! langGet('SeoInfo.ContactMetaDescription', [ 'year' => date("Y")]) !!}">    
@endsection

@section('content')
    <section>
        <div class="container">
            <div class="col-12 mb-lg-6 mb-6 px-0">
                <ul class="breadcrumb black px-0 mx-lg-0">
                    {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                    {!! breadcrumb(langGet('global.ContactHostelz')) !!}
                </ul>
                <h1 class="mb-3 mb-lg-5 pb-md-2">@langGet('global.ContactHostelz')</h1>

            @if ($reason == '')
                <div class="mb-3 text-primary"><h4>@langGet('contact.PleaseChooseWhy')</h4></div>

                <div class="mb-3 mb-sm-5">
                    <div class="row">
                            <div class="col-12">
                                <ul class="list-group">

                                    @if ($listing)
                                        @if ($listing->onlineReservations)
                                            <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'how-to-book')" class="text-dark">@langGet('contact.MakeARes', [ 'hostelname' => $listing->name, 'hostelcity' => $listing->city ])</a>
                                        @else
                                            <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'no-reservations')" class="text-dark">@langGet('contact.MakeARes', [ 'hostelname' => $listing->name, 'hostelcity' => $listing->city ])</a>
                                        @endif

                                        <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'contact-listing')" class="text-dark">@langGet('contact.Contact', [ 'hostelname' => $listing->name, 'hostelcity' => $listing->city ])</a>
                                    @endif

                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'about-booking')" class="text-dark">@langGet('contact.AskAboutRes')</a>
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'add-listing')" class="text-dark">@langGet('contact.AddMyHostel')</a>
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', [ 'contact-form', 'listings'])" class="text-dark">@langGet('contact.ListingQuestions')</a>
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', [ 'contact-form', 'press'])" class="text-dark">@langGet('contact.Press')</a>
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', [ 'contact-form' ])" class="text-dark">@langGet('contact.OtherQuestion')</a>
                                </ul>
                            </div>

                        </div>
                </div>
            @elseif ($reason == 'how-to-book')
                <div role="alert" class="alert alert-info">
                    <p>@langGet('contact.howToBook', [ 'hostelname' => $listing->name, 'hostelcity' => $listing->city, 'hostelURL' => $listing->getURL() ])</p>
                    <p>On Hostelz.com you can compare all hostel prices, so you can find the cheapest price instantly.</p>
                    
                    <p class="js-show-if-not-login"><a href="#signup" data-smooth-scroll="">Sign up with Hostelz.com</a> and get access to exclusive hostel content and much more.</p>
                   
                    <div class="text-center">
                        <div class="">
                            <a href="{{ $listing->getURL() }}" target="" title="Compare Prices for {{ $listing->name }}" class="btn btn-lg btn-outline-primary bg-primary-light mt-4 tt-n py-2 px-sm-5 font-weight-600 rounded">Compare Prices for {{ $listing->name }}</a>
                        </div>
                        <div class="mt-3 pt-2">
                            <?php $hwImported = $listing->activeImporteds()->where('system', 'BookHostels')->first(); ?>
                            @if ($hwImported) 
                                <a class="cl-link text-sm" target="_blank" rel="nofollow" title="Check {{ $listing->name }} at Hostelworld.com" href="{!! 'https://hostelworld.prf.hn/click/camref:1100l3SZe/pubref:contactPage/destination:' . urlencode($hwImported->urlLink) !!}">Check {{ $listing->name }} at Hostelworld.com</a>
                            @endif
                        </div>
                    </div>
                </div>
            
                @elseif ($reason == 'no-reservations')
                <div role="alert" class="alert alert-info">@langGet('contact.noReservationsForListing')</div>
            @elseif ($reason == 'contact-listing')
                <div role="alert" class="alert alert-info">@langGet('contact.aboutHostel')</div>
            @elseif ($reason == 'about-booking')
                <div class="text-primary mb-4">
                    <h4>@langGet('contact.MyReservation')</h4>
                </div>
                <div class="mb-4 mb-sm-5">
                    <div class="p-md-2">
                        <div class="row">
                            <div class="col-12">
                                <ul class="list-group">
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'confirm-booking')" class="text-dark">@langGet('contact.GetConfirmation')</a></li>
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'change-booking')" class="text-dark">@langGet('contact.ChangeOrCancel')</a></li>
                                    <li class="list-group-item dropdown-item"><a href="@routeURL('contact-us', 'contact-form')" class="text-dark">@langGet('contact.AskResQuestion')</a></li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
            @elseif ($reason == 'confirm-booking')
                <div class="text-primary mb-4">
                    <h4>@langGet('contact.ConfirmRes')</h4>
                </div>
                <div class="mb-4 mb-sm-5">
                    <div class="p-md-2">
                        <div class="row">
                            <div class="col-12">
                                <div role="alert" class="alert alert-info">@langGet('contact.WaitForConfirm')</div>
                                    {{--  <p><li>If you want to check the status of your reservation online, you can use the "My Bookings" system. The "My Bookings" link appears in the links at the bottom of any page on Hostelz.com. --}}

                                <div role="alert" class="alert alert-info">{!! str_replace('<a>', '<a href="'.routeURL('contact-us', 'contact-form').'" class="text-dark">', langGet('contact.IfNoConfirm')) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($reason == 'change-booking')
                <div class="text-primary mb-4">
                    <h4>@langGet('contact.ChangeRes')</h4>
                </div>
                <div class="mb-4 mb-sm-5">
                    <div class="p-md-2">
                        <div class="row">
                            <div class="col-12">
                                <div role="alert" class="alert alert-info">@langGet('contact.ToChangeOrCancel')</div>
                                <div role="alert" class="alert alert-info">{!! str_replace('<a>', '<a href="'.routeURL('contact-us', 'confirm-booking').'" class="text-dark">', langGet('contact.IfNotReceive')) !!}</div>
                                <div role="alert" class="alert alert-info">{!! str_replace('<a>', '<a href="'.routeURL('contact-us', 'contact-form').'" class="text-dark">', langGet('contact.IfDidReceive')) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($reason == 'add-listing')
                <div role="alert" class="alert alert-info">
                    <p class="">@langGet('submitNewListing.SubmitListingInfo')</p>
                    <div class="py-3 text-center">
                        <button class="btn-lg btn-primary mt-2 mt-sm-0 text-nowrap js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i>@langGet('global.Search')</button>	
                    </div>

                    <p class="pt-4"><b>Your hostel is not yet listed at Hostelz.com?</b> @langGet('contact.ToAddAHostel', ['AddLink' => routeURL('submitNewListing')] )</p>
                    <div class="text-center">
                        <a href="{!! routeURL('submitNewListing') !!}" target="" title="{!! langGet('global.NewListing') !!}" class="btn btn-lg btn-outline-primary bg-primary-light mt-4 tt-n py-2 px-sm-5 font-weight-600 rounded">{!! langGet('global.NewListing') !!}</a>
                    </div>
                </div>
                
            @elseif ($reason == 'contact-form')

                @if ($status == '')

                    @if ($contactType == 'press')
                        <h2>@langGet('contact.Press')</h2>
                        <p>@langGet('contact.PressText')</p>
                    @elseif ($contactType == 'listings')
                        <p>@langGet('contact.ClaimListing')</p>
                    @else
                        @langGet('contact.CommentInstructions')
                    @endif
                    <form method="post">
                        <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="name">@langGet('contact.YourName')</label>
                                    <input class="form-control" id="name" name="name" autofocus value="{{{ @$name }}}" placeholder="@langGet('contact.YourName')">
                                    @if (@$errors) <div class="text-danger bold italic">{!! $errors->first('name') !!}</div> @endif
                                </div>
                                <div class="form-group">
                                    <label for="email">@langGet('contact.YourEmailAddress')</label>
                                    <input class="form-control" id="email" name="email" value="{{{ @$email }}}" placeholder="@langGet('contact.YourEmailAddress')">
                                    @if (@$errors) <div class="text-danger bold italic">{!! $errors->first('email') !!}</div> @endif
                                </div>
                                <div class="form-group">
                                    <label for="subject">@langGet('contact.Subject')</label>
                                    <input class="form-control" id="subject" name="subject" value="{{{ @$subject }}}" placeholder="@langGet('contact.Subject')">
                                    @if (@$errors) <div class="text-danger bold italic">{!! $errors->first('subject') !!}</div> @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="message">@langGet('contact.Message')</label>
                                    <textarea class="form-control" id="message" name="message" rows=8 placeholder="@langGet('contact.Message')">{{{ @$message }}}</textarea>
                                    @if (@$errors) <div class="text-danger bold italic">{!! $errors->first('message') !!}</div> @endif
                                </div>
                            </div>
                        </div>

                        @if ($captcha) <p>{!! $captcha->formOutput() !!}</p> @endif

                        <button class="btn btn-primary" type="submit">@langGet('contact.SendIt')</button>
                    </form>

                @elseif ($status == 'sent')
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-success"><i class="fa fa-check-circle"></i>&nbsp; @langGet('contact.MessageSent')</div>
                        </div>
                    </div>
                @endif
            @else
                <?php logWarning("Unknown reason '$reason'."); ?>
            @endif

            </div>
        </div>
    </section>
@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop

