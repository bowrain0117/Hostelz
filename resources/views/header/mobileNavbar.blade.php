<div class="d-none d-lg-block">
{{--    <button type="button"--}}
{{--            data-toggle="collapse"--}}
{{--            data-target="#navbarCollapse"--}}
{{--            aria-controls="navbarCollapse"--}}
{{--            aria-expanded="false"--}}
{{--            aria-label="Toggle navigation"--}}
{{--            class="navbar-toggler navbar-toggler-right py-2">--}}
{{--        <i class="fa fa-bars"></i>--}}
{{--    </button>--}}

    <!-- Navbar Collapse -->
    <div id="navbarCollapse" class="collapse navbar-collapse">

        @if (isset($showHeaderSearch))
            <form action="{!! routeURL('search') !!}" id="searchcollapsed" class="form-inline mt-4 mb-sm-2 d-sm-none">
                <div class="input-label-absolute input-label-absolute-left input-reset input-expand ml-lg-2 ml-xl-3">
                    <label for="search_search" class="label-absolute"><i class="fa fa-search"></i><span class="sr-only">{{{ langGet('index.EnterAName') }}}</span></label>

                    <input id="search_search--collapsed" name="search" placeholder="{{{ langGet('index.EnterAName') }}}" aria-label="{{{ langGet('index.EnterAName') }}}" class="websiteSearch form-control border-1 shadow-0">
                    <button type="reset" class="btn btn-reset btn-sm"><i class="fa-times fas"></i></button>
                </div>
            </form>
        @endif

        <ul id="loggedIn" class="navbar-nav ml-auto flex-sm-row justify-content-sm-between">
            <div id="headerUserSettings">
                <div class="avatar mr-2 bg-gray-800 border border-white">
                    @include('partials.svg-icon', ['svg_id' => 'user-icon-dark', 'svg_w' => '44', 'svg_h' => '48'])
                </div>
            </div>

        </ul>

        <ul id="loggedOut" class="navbar-nav ml-auto flex-sm-row justify-content-sm-between">
            <a
                href="{{ route('comparison') }}"
                class="card-fav-icon position-relative z-index-40 opacity-9 bg-light comparison"
                data-toggle="tooltip"
                title="{{ __('comparison.startComparing') }}"
            >
                @include('partials.svg-icon', ['svg_id' => 'comparison-tool', 'svg_w' => '24', 'svg_h' => '28'])
                <span class="comparison-count ml-1"></span>
            </a>

            <li class="nav-item mt-3 mt-md-0 ml-md-3 rounded email">
                <a class="btn btn-primary tt-n" href="{!! routeURL('login') . (@$returnToThisPageAfterLogin ? '?returnTo=' . urlencode(Request::fullURL()) : '') !!}">@langGet('global.login')</a>
            </li>
        </ul>
    </div>
</div>