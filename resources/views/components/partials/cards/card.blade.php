@props(['item'])

<div class="col-lg-6 col-sm-6 mb-4 hover-animate" id="{{ $item->slug_id }}">
    <div class="card shadow border-0 h-100">

        <a href="{{ $item->path }}" title="{{ $item->title }}" class="card-img-wrap">
            <picture>
                <source srcset="{{ $item->thumbnail->src['thumb_webp'] }}" type="image/webp">
                <img
                        class="img-fluid card-img-top"
                        src="{{ $item->thumbnail->src['thumb_def'] }}"
                        alt="{{ $item->thumbnail->title }}"
                        title="{{ $item->thumbnail->title }}"
                        loading="lazy"
                >
            </picture>
        </a>

        <div class="card-body">
            <h6 class="card-title"><a class="text-decoration-none text-dark" href="{{ $item->path }}">{{ $item->title }}</a></h6>
        </div>

    </div>
</div>