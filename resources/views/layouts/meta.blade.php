@if (!isset($ogThumbnail) || $ogThumbnail !== null)
    {{-- (Apparently it has to be a full URL to work with Facebook.) --}}
    <meta property="og:image"
          content="{!! isset($ogThumbnail) ? $ogThumbnail : routeURL('images', 'hostelz-medium.png', 'absolute') !!}">
@endif
<meta property="og:site_name" content="Hostelz"/>
<meta property="og:url" content="{{ request()->url() }}"/>
<meta property="og:type" content="website"/>
<meta name="twitter:card" content="summary"/>
<meta name="twitter:site" content="@hostelz"/>
<meta name="twitter:creator" content="@hostelz"/>
<meta name="p:domain_verify" content="8f16cd1b02fa89aaab4ade1941964b56"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">

<link rel="canonical" href="{{ request()->fullUrl() }}">

@foreach ($hreflangLinks ?? \App\Models\Languages::currentUrlInAllLiveLanguages() as $langCode => $langUrl)
    {{-- Note that they want us to even include a link to this page --}}
    @php $languageName = \App\Models\Languages::get($langCode)->name; @endphp
    @if ($langCode === 'en')
        <link rel="alternate" type="text/html" hreflang="x-default" href="{{ url( $langUrl ) }}"
              title="{{ $languageName }}"/>
        <link rel="alternate" type="text/html" hreflang="en-gb" href="{{ url( $langUrl ) }}"
              title="{{ $languageName }} (UK)"/>
        <link rel="alternate" type="text/html" hreflang="en-us" href="{{ url( $langUrl ) }}"
              title="{{ $languageName }} (US)"/>
        <link rel="alternate" type="text/html" hreflang="en-au" href="{{ url( $langUrl ) }}"
              title="{{ $languageName }} (AU)"/>
    @else
        {{-- <link rel="alternate" type="text/html" hreflang="{!! \App\Models\Languages::get($langCode)->otherCodeStandard('IANA') !!}" href="https://www.hostelz.com{!! $langUrl !!}" title="{{ $languageName }}"/> --}}
    @endif
@endforeach  