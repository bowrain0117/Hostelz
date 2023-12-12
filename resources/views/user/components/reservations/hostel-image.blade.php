@props(['reservation'])

<div {{ $attributes->merge(['class' => 'col-12 col-lg-2 align-self-center mb-4 mb-lg-0']) }}>
    <div class="hostel-card bg-white w-100 hover-animate">
        <div class="hostel-card">
            <a target="_blank" href="{{ $reservation->hostelLink }}" title="{{ $reservation->hostelImage->title }}">
                <picture>
                    <source srcset="{{ $reservation->hostelImage->src['thumb_webp'] }}" type="image/webp">
                    <img class="img-fluid card-img-top"
                         src="{{ $reservation->hostelImage->src['thumb_def'] }}"
                         alt="{{ $reservation->hostelImage->title }}"
                         title="{{ $reservation->hostelImage->title }}"
                         loading="lazy"
                    >
                </picture>
            </a>
        </div>
    </div>
    {{ $slot }}
</div>