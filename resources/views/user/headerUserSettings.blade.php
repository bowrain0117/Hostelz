<a
    href="{{ route('comparison') }}"
    class="card-fav-icon position-relative z-index-40 opacity-9 bg-light comparison mt-1"
    data-toggle="tooltip"
    title="{{ __('comparison.startComparing') }}"
>
    @include('partials.svg-icon', ['svg_id' => 'comparison-tool', 'svg_w' => '24', 'svg_h' => '28'])
    <span class="comparison-count ml-1">{{ $comparisonListingsCount }}</span>
</a>
<li class="nav-item dropdown ml-lg-3" id="header-navbar-collapse-parent">
    <a id="userDropdownMenuLink" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @if ($avatar)
            <img id="headerUserAvatar" src="{!! $avatar !!}" alt="img" class="avatar avatar-border-white">
        @else
            <div class="avatar bg-gray-800 border border-white">
                @include('partials.svg-icon', ['svg_id' => 'user-icon-dark', 'svg_w' => '44', 'svg_h' => '48'])
            </div>
        @endif
    </a>
    <div aria-labelledby="userDropdownMenuLink" class="dropdown-menu dropdown-menu-right" id="header-navbar-collapse">
        <a href="{!! routeURL('user:menu') !!}" class="dropdown-item">{!! langGet('User.menu.UserMenu') !!}</a>
        <a href="{!! routeURL('wishlist:index') !!}" class="dropdown-item">{!! langGet('User.menu.Wishlists') !!}</a>
        <a href="{!! routeURL('articles', "best-hostel-tips-backpacking") !!}" class="dropdown-item">{!! langGet('User.menu.ExclusiveContent') !!}</a>

        <div class="dropdown-divider"></div>

        <div class="dropdown-item currencySelectorMenuPlaceholder"></div>

        @if(0)

        <a href="#" class="dropdown-item" data-toggle="dropdown">
            <img src="{!! routeURL('images', 'flags/' . $language . '.svg') !!}"
                 alt="flag"
                 style="width: 24px" />
            {!! __('LanguageNames.native.' . $language) !!}
        </a>
        <div class="dropdown-menu language-dropdown" aria-expanded="false" style="top: 130px">
            @foreach (\App\Models\Languages::allLiveSiteCodes() as $langCode)

                <a href="{{ route('change-language', $langCode) }}" class="dropdown-item">

                    @if ($langCode)
                        <img
                                data-src="{{ routeURL('images', 'flags/' . $langCode . '.svg') }}"
                                class="lazyload"
                                src="" alt="flag" style="width: 24px" />
                    @endif

                    {!! __('LanguageNames.native.'.$langCode) !!}
                </a>

            @endforeach
        </div>
        @endif

        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{!! routeURL('logout') !!}">@langGet('global.logout')</a>
    </div>
</li>