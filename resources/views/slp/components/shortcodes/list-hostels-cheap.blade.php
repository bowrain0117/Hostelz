<div>
    <h3 id="cheapest-hostels" class="font-weight-bold">Sorted by Price: The Cheapest Hostels in {{ $subjectName }}</h3>
    <ol>
        @foreach($listings as $listing)
            <li>
                <a href="{{ $listing['url'] }}" target="_blank" rel="nofollow" title="{{ $listing['name'] }}"
                   class="cl-primary">
                    {{ $listing['name'] }}
                </a>
                @if(filled($listing['minPrice']))
                    - from {{ $listing['minPrice'] }}
                @endif
            </li>
        @endforeach
    </ol>
</div>