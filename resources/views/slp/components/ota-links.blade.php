@props(['listing', 'otaLinks'])

@php
    if ($otaLinks->all->isEmpty()) {
        return;
    }
@endphp

<div class="listingInfoLine mb-3 text-center">
    <a class="btn btn-lg btn-danger rounded px-5"
       href="{{ $otaLinks->main->link }}" target="_blank"
       title="Compare Prices for {{ $listing['name'] }}"
    >
        Book here {{ $listing['name'] }}
    </a>
</div>

<div class="mb-3 row justify-content-around">
    Check:
    @foreach($otaLinks->all as $ota)
        <a href="{!! $ota->link !!}"
           onclick="ga('send', 'event', 'single', 'SLP', '{{{ $listing['name'] }}}, {!! $listing['city'] !!} - {{ $ota->shortName }}')"
           title="{{{ $listing['name'] }}} {!! langGet('listingDisplay.At') !!} {{ $ota->shortName }}"
           target="_blank" class="font-weight-bold" rel="nofollow"
        >
            @include('partials.svg-icon', ['svg_id' => strtolower($ota->name) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
            {{ $ota->shortName }}
        </a>

        @if($loop->remaining !== 0)
            |
        @endif
    @endforeach

    |
    <a href="{{ $listing['url'] }}"
       title="Get the Best Deal for {{ $listing['name'] }}"
       target="_blank" class="font-weight-bold"
    >
        @include('partials.svg-icon', ['svg_id' => 'hostelz-logo-sm', 'svg_w' => '22', 'svg_h' => '22'])
        Get the Best Deal
    </a>
</div>
