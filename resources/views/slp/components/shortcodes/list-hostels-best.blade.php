@php
    if ($listings->isEmpty()) {
        return;
    }
@endphp

<p class="font-weight-bold">{{ $title }}</p>
<ol>
    @foreach($listings as $listing)
        <li>
            <a href="{{ $listing['url'] }}" target="_blank" rel="nofollow" title="{{ $listing['name'] }}"
               class="cl-primary">
                {{ $listing['name'] }}
            </a>
            @if(filled($listing['goodFor']))
                - best for {{ implode(', ', $listing['goodFor']) }}
            @endif
        </li>
    @endforeach
</ol>