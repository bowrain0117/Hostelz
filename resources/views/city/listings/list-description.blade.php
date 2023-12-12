<div class="mb-3 tx-small text-break" property="description">
    @if (isset($snippets[$listing->id]))
        {!! wholeWordTruncate($snippets[$listing->id], 200) !!}
    @else
        {{ $listing->address }} ...
    @endif
    <a href="{!! $listing->getURL() !!}" title="{{{ $listing->name }}}" target="_blank"
       class="font-weight-600 text-lowercase cl-text">@langGet('city.more')</a>
</div>