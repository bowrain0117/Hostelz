@php
    if ($slps->isEmpty() && $categoryPages->isEmpty()) {
        return;
    }
@endphp

<h2 id="hostel-guides" class="sb-title cl-text mb-3 d-none d-lg-block">Find your Perfect Hostel Match</h2>
<p>We've created more special guides for you:</p>

<div>
    @foreach($categoryPages as $category)
        <p><a href="{{ $category->url }}" title="{{ $category->title }}">{{ $category->title }}</a></p>
    @endforeach
</div>

<div>
    @foreach($slps as $slp)
        <a href="{{ $slp->path }}" title="{{ $slp->title }}" class="shadow-1 rounded mb-4 overflow-hidden d-block disableHoverUnderline">
            @if($slp->thumbnail)
                <picture>
                    <source srcset="{{ $slp->thumbnail->src['thumb_webp'] }}" type="image/webp">
                    <img
                            class="w-100"
                            src="{{ $slp->thumbnail->src['thumb_def'] }}"
                            alt="{{ $slp->thumbnail->title }}"
                            title="{{ $slp->thumbnail->title }}"
                            loading="lazy"
                    >
                </picture>
            @endif
            <p class="p-3 p-sm-4 cl-text mb-0">{{ $slp->title }}</p>
        </a>
    @endforeach
</div>