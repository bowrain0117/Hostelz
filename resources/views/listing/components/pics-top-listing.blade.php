@php
    if($listingPicsList->isEmpty()) {
        return;
    }
@endphp

<section class="position-relative" id="vue-top-pics-slider">
    <div>
        <slider
                :pics-list="{{ $listingPicsList }}"
                :pic-group="'{{ $picGroup }}'"
        ></slider>
    </div>
    <div style="font-size: 20px;" class="d-block d-lg-none position-absolute top-0 right-0 p-3 z-index-50">
        <x-wishlist-icon :listing-id="$listing->id"/>
        <comparison-icon :listing-id="{{ $listing->id }}"/>
    </div>

</section>
