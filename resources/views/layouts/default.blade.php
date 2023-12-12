<?php

use Lib\HttpAsset;

HttpAsset::requireAsset('fontAwesome5');
HttpAsset::requireAsset('new-styles.css');
HttpAsset::requireAsset('custom.css');

HttpAsset::requireAsset('libs.js');
HttpAsset::requireAsset('global.js');
?>

@if (!empty($themeTemplate))
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

	{!! HttpAsset::output('css', 'header') !!}

	@stack('styles')

	<title>@yield('title', 'Hostelz.com')</title>

	@yield('header')

	{{-- Favicons --}}
	<link id="favicon" rel="shortcut icon" href="/favicon.ico">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#ff6059">
	<meta name="apple-mobile-web-app-title" content="Hostelz">
	<meta name="application-name" content="Hostelz">
	<meta name="msapplication-TileColor" content="#ff6059">
	<meta name="theme-color" content="#ffffff">
	<script type="application/ld+json" nonce="">
		{
			"@context": "http://schema.org",
			"@type": "Organization",
			"name": "Hostelz.com",
			"alternateName": "Hostelz",
			"brand": "Hostelz.com",
			"url": "{!! routeURL('home', [ ], 'absolute') !!}",
            "logo": "{!! routeURL('images', 'hostelz-small.png', 'absolute') !!}",
            "sameAs":[
                "https://www.facebook.com/hostelz",
                "https://www.instagram.com/hostelz/",
                "https://twitter.com/hostelz",
                "https://youtube.com/@Hostelz",
                "https://www.pinterest.com/hostelz",
                "https://www.crunchbase.com/organization/hostelz"],
		{{-- todo: add our search parameters --}}
		"potentialAction": {
			"@type": "SearchAction",
			"target": "{!! routeURL('search', [ ], 'absolute') !!}?search={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }


	</script>

	@yield('headerJsonSchema')

	@include('layouts.meta')

	@if (App::environment('production') && !Request::is('staff') && !Request::is('staff/'))
		{{-- Google Analytics Site Tag manager --}}
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-86766-1"></script>
		<script>
            (function (i, s, o, g, r, a, m) {
                i['GoogleAnalyticsObject'] = r;
                i[r] = i[r] || function () {
                    (i[r].q = i[r].q || []).push(arguments)
                }, i[r].l = 1 * new Date();
                a = s.createElement(o),
                    m = s.getElementsByTagName(o)[0];
                a.async = 1;
                a.src = g;
                m.parentNode.insertBefore(a, m)
            })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');

            ga('create', 'UA-86766-1', 'auto');
            ga('set', 'anonymizeIp', true);
            ga('send', 'pageview');
			@if (@$adWordsConversionTrackingCode)
            gtag('event', 'conversion', {'send_to': '{{ $adWordsConversionTrackingCode }}'});
			@endif
		</script>

		{{-- Google tag (gtag.js) --}}
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-6ZD2688Z1E"></script>
		<script> window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', 'G-6ZD2688Z1E'); </script>

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

		<!-- Google Tag Manager -->
		<script>(function (w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({
                    'gtm.start':
                        new Date().getTime(), event: 'gtm.js'
                });
                var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', 'GTM-PRVM5HX');</script>
		<!-- End Google Tag Manager -->
	@endif
</head>

<body @yield('bodyAttributes')>

<x-load-svg-icons/>

@include('layouts/header')

@if (!empty($themeTemplate))
	@yield('themeTemplateHeader')
@elseif (!empty($customHeader))
	{!! $customHeader !!}
@else

@endif

{{-- ** CONTENT ** --}}
@yield('content')

@if (!isset($disableStickyFooter))
	<div id="push"></div> {{-- Needed to keep the sticky footer from running into the page content. --}}
@endif

@include('partials/signupfooter')

@if (!empty($themeTemplate))
	@yield('themeTemplateFooter')
@elseif (!empty($customFooter))
	{!! $customFooter !!}
@else
	@include('layouts/footer')
@endif

{!! HttpAsset::output('css', 'bottom') !!}

@include('js.global-options')

@stack('scriptOptions')

@stack('beforeScripts')

{!! HttpAsset::output('js') !!}

@yield('pageBottom')

@stack('scripts')

@if (App::environment('production') && !Request::is('staff') && !Request::is('staff/'))
	<!-- Google Tag Manager (noscript) -->
	<noscript>
		<iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PRVM5HX"
		        height="0" width="0" style="display:none;visibility:hidden"></iframe>
	</noscript>
	<!-- End Google Tag Manager (noscript) -->
@endif

</body>
</html>
