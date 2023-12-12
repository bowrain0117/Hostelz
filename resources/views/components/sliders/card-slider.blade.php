<div class="mb-5 border-0 text-center shadow-lg position-relative">

    <div class="card-img-overlay-top d-flex justify-content-end">
        <x-wishlist-icon :listing-id="$listingId"/>
        <comparison-icon :listing-id="{{ $listingId }}"/>
    </div>

    <slider :pics="{{ $pics }}" :listing="{{ $listing }}"></slider>
</div>