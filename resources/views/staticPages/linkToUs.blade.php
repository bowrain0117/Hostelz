<?php

Lib\HttpAsset::requireAsset('booking-main.js');

?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('linkToUs.LinkToHostelz').' - Hostelz.com')

@section('header')
@stop

@section('content')
    <section class="mb-5">
        <!--  Breadcrumbs  -->
        <div class="container">
            <ul class="breadcrumb black px-0 mx-sm-n3 mx-lg-0">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('linkToUs.LinkToHostelz')) !!}
            </ul>

            <h1 class="mb-3 mb-lg-5 pb-md-2">@langGet('linkToUs.LinkToHostelz')</h1>
            <p>@langGet('linkToUs.LinkToHostelzText')</p>

            <h2 class="mt-4">@langGet('linkToUs.LinkToHTMLTitle')</h2>
            <p>@langGet('linkToUs.HereIsSomeHTML')</p>

            <h3 class="mt-4 h4">@langGet('linkToUs.LinkToStartPage')</h3>
            <pre class="mb-3">{!! htmlentities('<p><b><a href="https://www.hostelz.com">Hostels Guide Hostelz.com</a></b></p>') !!}</pre>

            <h3 class="mt-4 h4">@langGet('linkToUs.LinkToCity')</h3>
            <pre class="mb-3">{!! htmlentities('<p><b><a href="https://www.hostelz.com/hostels/Netherlands/Amsterdam" title="Amsterdam Hostels">Hostels in Amsterdam</a></b></p>') !!}</pre>
            <i>@langGet('linkToUs.LinkToCityChangeText')</i>

            <h3 class="mt-4 h4">@langGet('linkToUs.LinkWithAnImageTitle')</h3>
            <p>@langGet('linkToUs.LinkWithAnImage')</p>

            <pre class="mb-3">{!! htmlentities('<p><a href="https://www.hostelz.com" title="Hostel Comparison"><img src="https://www.hostelz.com/images/hostelz-small.png" style="width: 120px" title="Hostel Comparison" alt="Hostel Comparison"></a></p>') !!}</pre>

            <p class="mt-4">@langGet('linkToUs.LooksLikeThis')</p>

            <p><a href="https://www.hostelz.com" title="Hostel Comparison"><img src="https://www.hostelz.com/images/hostelz-small.png" style="width: 120px" title="Hostel Comparison" alt="Hostel Comparison"></a></p>
            <hr class="my-5">

            <h2>@langGet('linkToUs.LinkWithJournalistTitle')</h2>
            <p>@langGet('linkToUs.LinkWithJournalistText')</p>
            <p class="text-center"><a href="@routeURL('contact-us', [ 'contact-form', 'press'])"  class="btn btn-lg btn-outline-primary bg-primary-light mt-4 tt-n py-2 px-sm-5 font-weight-600 rounded">@langGet('linkToUs.LinkWithJournalistContact')</a></p>

            <hr class="my-5">

            <h2>@langGet('global.Blog')</h2>
            <p>@langGet('linkToUs.LinkWithBlogText')</p>
            <p class="text-center"><a href="@routeURL('articles')"  class="btn btn-lg btn-outline-primary bg-primary-light mt-4 tt-n py-2 px-sm-5 font-weight-600 rounded">@langGet('linkToUs.LinkWithBlogTextRead')</a></p>

        </div>
    </section>
@stop

@section('pageBottom')
    @parent

    <script>
      initializeTopHeaderSearch();
    </script>
@stop
