<?php ?>

@php
    $navClass = $navClass ?? 'navbar-expand-lg';
    $showHeaderSearch = $showHeaderSearch ?? false;
@endphp

<header class="header bg-white">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav class="navbar {{$navClass}} navbar-light justify-content-between">
                    <a href="{!! routeURL('home') !!}" class="navbar-brand"
                       title="Hostelz.com - the worlds largest hostel database">
                        <span class="d-none d-lg-inline">
                            @include('partials.svg-icon', ['svg_id' => 'hostelz-logo', 'svg_w' => '104', 'svg_h' => '24'])
                            <br>
                            <span class="text-sm">Price Comparison</span>
                        </span>
                        <span class="d-lg-none">
                            @include('partials.svg-icon', ['svg_id' => 'hostelz-logo-mobile', 'svg_w' => '69', 'svg_h' => '16'])
                            <br>
                            <span class="text-xxs">Price Comparison</span>
                        </span>
                    </a>

                    @if ($showHeaderSearch)
                        @include('header.search.searchResult')
                    @endif

                    @include('header.mobileNavbar')

                    @yield('headerNavBottom')

                    @if ($showHeaderSearch)
                        @include('header.mobileSearch')
                    @endif
                </nav>
            </div>

            @if ($showHeaderSearch)
                @include('header.search.searchForm')
            @endif

        </div>
    </div>
</header>
<div id="header-search-overlay"></div>
