{{-- Note that there is no need to add any route on the dynamic site because those are *all* disallowed currently! --}}

User-agent: GPTBot
Disallow: /

@if(App::environment('production'))

    User-agent: *

    @if ($domain === 'static')

        Disallow: /listing-website/
        Disallow: /wishlists
        Disallow: /search

        @foreach($disallowLinks as $link)
            Disallow: {{ $link }}
        @endforeach

        Sitemap: {{ routeURL('home', [], 'absolute') }}/sitemap_index.xml

    @elseif ($domain === 'dynamic')

        Disallow: /

    @endif

@else

    User-agent: *
    Disallow: /

    User-agent: AdsBot-Google
    Disallow: /

@endif
