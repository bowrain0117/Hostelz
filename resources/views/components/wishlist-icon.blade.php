@props(['listingId', 'heartColor' => 'primary', 'zIndex' => 2])

<span
        {{ $attributes->class(["wishlistHeartWrap card-fav-icon position-relative z-index-{$zIndex} opacity-9 bg-light"]) }}
>
    <i class="wishlistHeart far fa-heart wishlistListing-{{ $listingId }} cl-{{ $heartColor }}"
       data-listing="{{ $listingId }}"></i>
</span>