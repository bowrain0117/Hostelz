@php use App\Models\Languages; @endphp

<li class="list-inline-item">
    <div class="dropdown dropup">
        <button class="btn noboxshadow btn-default text-white font-weight-normal text-capitalize" type="button" data-toggle="dropdown">
            <i class="fas fa-globe"></i> {!! langGet('LanguageNames.native.' . Languages::currentCode()) !!}
        </button>
        <ul class="dropdown-menu language-dropdown">
            @foreach ($hreflangLinks ?? Languages::currentUrlInAllLiveLanguages() as $langCode => $langUrl)
                <li>
                    <a role="menuitem" tabindex="-1" href="{{{ $langUrl }}}">
                        <button class="btn noboxshadow">

                            @if ($langCode)
                                <img
                                        data-src="{!! routeURL('images', 'flags/' . $langCode . '.svg') !!}"
                                        class="lazyload"
                                        src="" alt="flag" style="width: 24px" />
                            @endif

                            {!! langGet('LanguageNames.native.'.$langCode) !!}

                        </button>
                    </a>

                </li>
            @endforeach
        </ul>
    </div>
</li>