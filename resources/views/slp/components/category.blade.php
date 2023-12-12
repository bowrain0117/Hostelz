<div class="row mb-5">

    @forelse ($items as $item)
        <div class="col-lg-4 col-sm-6 mb-4 hover-animate">
            <div class="card shadow border-0 h-100">

                <a href="{{ $item->path }}" title="{{ $item->title }}" class="card-img-wrap">
                    <picture>
                        <source srcset="{{ $item->thumbnail->src['thumb_webp'] }}" type="image/webp">
                        <img
                                class="img-fluid card-img-top lazyload blur-up"
                                src="{{ $item->thumbnail->src['tiny'] }}"
                                data-src="{{ $item->thumbnail->src['thumb_def'] }}"
                                alt="{{ $item->thumbnail->title }}"
                                title="{{ $item->thumbnail->title }}"
                                loading="lazy"
                        >
                    </picture>
                </a>

                <div class="card-body">
                    <h4 class="mb-0">
                        <a href="{{ $item->path }}" class="text-dark" title="{{ $item->title }}">
                            {{ $item->title }}
                        </a>
                    </h4>
                </div>

            </div>
        </div>
    @empty
        <p>No <span class="text-capitalize">{{ $category->title() }}</span> listed yet. We are on it! This is gonna be
            amazing!</p>
    @endforelse

</div>

@if($listings->hasPages())
    <div class="row my-5">
        <div class="col">
            {{ $pagination }}
        </div>
    </div>
@endif