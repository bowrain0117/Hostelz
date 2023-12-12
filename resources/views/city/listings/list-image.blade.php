<div class="position-relative">
    <div class="card-img-overlay-top d-flex justify-content-end">
        <x-wishlist-icon :listing-id="$listing->id"/>
        <comparison-icon :listing-id="{{ $listing->id }}"/>
    </div>

    @if (
        $cityInfo &&
        $cityInfo->hostelCount > 1 &&
        ($cityInfo->topRatedHostel === $listing->id || $cityInfo->cheapestHostel === $listing->id)
    )
        <div class="position-absolute top-0 left-0 z-index-10 p-3">

            @if ($cityInfo && $cityInfo->hostelCount > 1 && $cityInfo->topRatedHostel === $listing->id)
                <div class="pre-title bg-primary py-1 px-2 cl-light rounded-sm mb-3">
                    {{ __('listingDisplay.TopRatedHostelShort') }}
                </div>
            @endif

            @if ($cityInfo && $cityInfo->hostelCount > 1 && $cityInfo->cheapestHostel === $listing->id)
                <div class="pre-title bg-success py-1 px-2 cl-light rounded-sm">
                    $ {{ __('listingDisplay.CheapestHostelShort') }}
                </div>
            @endif

        </div>
    @endif

    <x-sliders.listing :picUrls="$picUrls[$listing->id]" :listing="$listing"/>

</div>