<div id="userBottomMenu" class="d-lg-none toogleOnScroll">
    <a class="bottom-menu-item col-3" href="{!!  routeURL('home') !!}" title="Home">
        @include('partials.svg-icon', ['svg_id' => 'logo-min', 'svg_w' => '15', 'svg_h' => '24'])
        <span class="pre-title cl-primary">@langGet('global.Home')</span>
    </a>

    <a class="bottom-menu-item col-3" href="{!! routeURL('wishlist:index') !!}" title="Wishlists">
        @include('partials.svg-icon', ['svg_id' => 'heart', 'svg_w' => '25', 'svg_h' => '24'])
        <span class="pre-title cl-subtext">@langGet('User.menu.Wishlists')</span>
    </a>

    <a class="comparison-sticky-mobile bottom-menu-item col-3"
       href="{{ route('comparison') }}"
       title="Comparison"
    >
        <div class="position-relative">
            @include('partials.svg-icon', ['svg_id' => 'comparison-tool', 'svg_w' => '24', 'svg_h' => '28'])
            <span class="comparison-count ml-1"></span>
        </div>
        <span class="pre-title cl-subtext">{{ __('comparison.comparison') }}</span>
    </a>

    <a id="userBottomMenuLogin" class="bottom-menu-item col-3" href="{!! routeURL('login') !!}"
       title="@langGet('global.login')">
        @include('partials.svg-icon', ['svg_id' => 'user-icon', 'svg_w' => '25', 'svg_h' => '24'])
        <span class="pre-title cl-subtext">@langGet('global.login')</span>
    </a>

    <a id="userBottomMenuProfile" class="bottom-menu-item col-4" href="{!! routeURL('user:menu') !!}"
       style="display: none;" title="User Settings">
        @include('partials.svg-icon', ['svg_id' => 'user-icon', 'svg_w' => '25', 'svg_h' => '24'])
        <span class="pre-title cl-subtext">@langGet('User.menu.UserMenu')</span>
    </a>
</div> 