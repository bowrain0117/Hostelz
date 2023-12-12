<?php

/*

Input variables:
    ogThumbnail - To specify a different thumbnail for Facebook links.

*/

use Lib\HttpAsset;

HttpAsset::requireAsset('bootstrap-js');
HttpAsset::requireAsset('fontAwesome');
HttpAsset::requireAsset('global.css');
HttpAsset::requireAsset('global.js');
HttpAsset::requireAsset('admin.js');
HttpAsset::requireAsset('autocomplete');

?>
@if (@$themeTemplate != '')
@include($themeTemplate)
@endif

		<!DOCTYPE html>
<html id="backend" lang="{!! \App\Models\Languages::current()->otherCodeStandard('IANA') !!}">
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

	@include('layouts.meta')

</head>

<body @yield('bodyAttributes') >

<div @if (!isset($disableStickyFooter)) id="wrap" @endif>{{-- needed for sticky footer --}}

	@if (@$themeTemplate != '')
		@yield('themeTemplateHeader')
	@elseif (@$customHeader !== null)
		{!! $customHeader !!}
	@else

		<header class="header">
			<div class="container">

				@if (!@$homepageHeader)
					<a href="{!! routeURL('home') !!}" class="navbar-brand"><img
								src="{!! routeURL('images', 'logo-header.png') !!}"
								alt="Hostelz.com - the worlds largest hostel database" height="24px"></a>
				@endif

				{{--<div>
					<div class="dropdown ourDropdownMenus">
						<button class="btn btn-default dropdown-toggle btn-sm" type="button" id="dropdownMenu1" data-toggle="dropdown">
							<i class="fa fa-globe text-primary"></i>{!! Languages::currentCode() == 'en' ? '' : '&nbsp; ' . langGet('LanguageNames.native.'.Languages::currentCode()) !!} &nbsp;<span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
							@foreach (@$hreflangLinks ? $hreflangLinks : Languages::currentUrlInAllLiveLanguages() as $langCode => $langUrl)
								<li role="presentation" @if ($langCode == Languages::currentCode()) class="menuOptionSelected" @endif>
									<a role="menuitem" tabindex="-1" href="{{{ $langUrl }}}">
										<i class="fa fa-check"></i>
										{!! langGet('LanguageNames.native.'.$langCode) !!}
									</a>
								</li>
							@endforeach
						</ul>
					</div>

				</div>--}}

				<div>
					<div id="loggedIn">
						<div>

							<a href="@routeURL('user:menu')"
							   style="margin-right: 10px;">@langGet('User.menu.UserMenu')</a>
							<a class="btn btn-sm btn-default"
							   href="{!! routeURL('logout') !!}">@langGet('global.logout')</a>
						</div>
					</div>

					<div id="loggedOut">
						{{-- <div class="pull-left hidden-xs">{!! langGet('global.tagline') !!}</div> --}}
						<div><a class="btn btn-sm btn-primary"
						        href="{!! routeURL('login') . (@$returnToThisPageAfterLogin ? '?returnTo=' . urlencode(Request::fullURL()) : '') !!}">@langGet('global.login')</a>
						</div>
					</div>
				</div>

				{{--<div class="headerSearch">
					@if (!@$homepageHeader)
						<form action="{!! routeURL('search') !!}" method="get" target="_top">
							<div class="input-group">
								<input type=text class="form-control input-sm websiteSearch" name="search" placeholder="{{{ langGet('global.FindHostelsIn') }}}">
								<span class="input-group-btn">
										<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-search"></i></button>
									</span>
							</div>
						</form>
					@endif
				</div>--}}

				{{--@if (!@$homepageHeader)
					<div class="hidden-xs headerMoto">
						<div>Hostelz.com is the only website with free listings</div>
						<div>and information about all hostels worldwide.</div>
					</div>
				@endif--}}
			</div>

		</header>

	@endif

	@if (@$showFeaturePill)
		<a class="featurePill hidden-sm hidden-xs" href="@routeURL('home')/how-to-compare-hostel-prices-hostelz">
			<div style="font-style: italic">Did you know ...</div>
			<div>Over 15 million people have used</div>
			<div>Hostelz.com to find and book hostels.</div>
			{{-- Previously: Why is Hostelz.com the #1</div><div>hostel information website? --}}
		</a>
	@endif

	{{-- ** CONTENT ** --}}
	@yield('content')

	@if (!isset($disableStickyFooter))
		<div id="push"></div> {{-- Needed to keep the sticky footer from running into the page content. --}}
	@endif

</div>

@if (@$themeTemplate != '')
	@yield('themeTemplateFooter')
@elseif (@$customFooter !== null)
	{!! $customFooter !!}
@else

	{{--<footer class="footer">

	</footer>--}}

@endif

@include('js.global-options')

{!! HttpAsset::output('css', 'bottom') !!}
{!! HttpAsset::output('js') !!}

@yield('pageBottom')

@stack('scripts')

</body>
</html>
