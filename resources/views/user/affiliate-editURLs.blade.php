<?php
    use Lib\Currencies;
    use App\Services\Payments;
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', langGet('global.AffiliateProgram'))

@section('content')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb(langGet('global.AffiliateProgram')) !!}
        </ol>
    </div>
    
    <h1 class="hero-heading h2">@langGet('global.AffiliateProgram')</h1>
        
	@if ($formHandler->mode == 'updateForm')
        <p>Enter the URLs (addresses) of your website or websites here. For example, if you enter "http://www.example.com", then any bookings from any links anywhere on the website www.example.com will be credited to your account.  Please be aware that you may be asked to prove that you own the website before your first payment is sent.</p>
    @endif
        
    @include('Lib/formHandler/doEverything', [ 'itemName' => 'Affiliate URLs', 'showTitles' => false, 'returnToURL' => routeURL('affiliate:menu') ])
</div>
@stop