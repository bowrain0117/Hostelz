@props([
    'picUrls',
    'listing'
])

<div {{ $attributes->class(['list-card-slider hostel-tile__img']) }}
     x-data="{}"
     x-init="new Swiper($refs.container, {
            modules: [Navigation, Pagination],
            slidesPerView: 1,
            spaceBetween: 0,
            centeredSlides: true,
            lazyPreloadPrevNext: 1,
            pagination: {
                el: '.swiper-pagination',
                type: 'bullets',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
    });"
>
    <div class="swiper"
         style="width: 100%; height: 100%; background: url({{ $picUrls->first()?->src['tiny'] }}) center no-repeat; background-size: cover;"
         x-ref="container"
    >
        <div class="swiper-wrapper">

            @foreach($picUrls as $items)
                <div class="swiper-slide">
                    <a
                            href="{{ $listing->getURL() }}"
                            target="_blank"
                            class="text-decoration-none cl-text"
                            title="{{ $listing->name }}"
                    >
                        @if($loop->last && $loop->count > 1)
                            <div class="last-slide-text">More Photos</div>
                        @endif

                        <picture>
                            <source srcset="{{ $items->src['thumb_webp'] }}" type="image/webp">

                            <img src="{{ $items->src['thumb_def'] }}"
                                 alt="{{ $items->title }}"
                                 style="object-fit: cover; width: 100%; height: 100%;"
                                 loading="lazy"
                            >
                        </picture>

                        <div class="swiper-lazy-preloader"></div>
                    </a>
                </div>
            @endforeach

        </div>

        <div class="swiper-pagination"></div>

        <div class="swiper-button-prev">
            @include('partials.svg-icon', ['svg_id' => 'slider-arrow-prev', 'svg_w' => '44', 'svg_h' => '48'])
        </div>
        <div class="swiper-button-next">
            @include('partials.svg-icon', ['svg_id' => 'slider-arrow-next', 'svg_w' => '44', 'svg_h' => '48'])
        </div>

    </div>
</div>