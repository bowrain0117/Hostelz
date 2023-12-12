<?php

use Lib\HttpAsset;

HttpAsset::requireAsset('fontAwesome5');
HttpAsset::requireAsset('new-styles.css');
HttpAsset::requireAsset('custom.css');
?>
@if (@$themeTemplate != '')
@include($themeTemplate)
@endif
		<!DOCTYPE html>
<html lang="{!! \App\Models\Languages::current()->otherCodeStandard('IANA') !!}">
<head>
	{{-- Recommended by bootstrap docs. Tells IE to use latest rending engine. (needs to be near the top of the page) --}}
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta charset="utf-8">
	{{-- Recommended by bootstrap docs. --}}
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	{{-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries (Bootstrap recommended) --}}
	<!--[if lt IE 9]>
	<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/respond.js/1.2.0/respond.js"></script>
	<![endif]-->

	{!! HttpAsset::output('css') !!}

	<title>@yield('title', 'Hostelz.com')</title>

	@yield('header')

	{{-- Favicons --}}
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#ff6059">
	<meta name="apple-mobile-web-app-title" content="Hostelz">
	<meta name="application-name" content="Hostelz">
	<meta name="msapplication-TileColor" content="#ff6059">
	<meta name="theme-color" content="#ffffff">
	<link href="https://fonts.googleapis.com/css?family=Montserrat:500,600,700&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700&display=swap" rel="stylesheet">
	{{-- TO DO
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="{{{ asset('assets/ico/apple-touch-icon-144-precomposed.png') }}}">
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="{{{ asset('assets/ico/apple-touch-icon-114-precomposed.png') }}}">
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="{{{ asset('assets/ico/apple-touch-icon-72-precomposed.png') }}}">
	<link rel="apple-touch-icon-precomposed" href="{{{ asset('assets/ico/apple-touch-icon-57-precomposed.png') }}}">
	--}}

	@include('layouts.meta')

	@if (App::environment('production') && !Request::is('staff') && !Request::is('staff/'))
		{{-- Google Analytics Site Tag manager --}}
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-86766-1"></script>
		<script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', 'UA-86766-1');

			@if (@$adWordsConversionTrackingCode)
            gtag('event', 'conversion', {'send_to': '{{ $adWordsConversionTrackingCode }}'});
			@endif
		</script>

		{{-- Hotjar Tracking Code for https://www.hostelz.com --}}
		<script>
            (function (h, o, t, j, a, r) {
                h.hj = h.hj || function () {
                    (h.hj.q = h.hj.q || []).push(arguments)
                };
                h._hjSettings = {hjid: 1552232, hjsv: 6};
                a = o.getElementsByTagName('head')[0];
                r = o.createElement('script');
                r.async = 1;
                r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
                a.appendChild(r);
            })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=');
		</script>
	@endif
</head>

<body vocab="http://schema.org/" typeof="WebPage" @yield('bodyAttributes') >

{{-- ** CONTENT ** --}}
@yield('content')

{!! HttpAsset::output('js') !!}

@yield('pageBottom')

</body>
</html>
