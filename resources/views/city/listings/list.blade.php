<div class="mb-lg-6 mb-4">

    @foreach ($listings as $listing)
        <div
                data-listing-id="{!! $listing->id !!}"
                typeof="Hostel"
                class="d-flex flex-column flex-lg-row mb-5 mb-lg-6 listing"
        >

            @php
                $features = \App\Models\Listing\ListingFeatures::getDisplayValues($listing->compiledFeatures);
                $review = $listing->getLiveReview();

                $schema = (new App\Schemas\HostelSchema($listing, $review, $features))->getSchema();
            @endphp

            @push('schema-scripts')
                {!! $schema !!}
            @endpush

            @include('city.listings.list-image')

            <div class="col-xl-6 p-0 list-description mx-0 mx-lg-5">
                <div class="d-flex flex-row justify-content-between">
                    <div>
                        @include('city.listings.list-title')

                        @include('city.listings.list-title-tags')
                    </div>

                    @include('city.listings.list-title-rating')

                </div>

                @include('city.listings.list-distance')

                @include('city.listings.list-features')

                @include('city.listings.list-description')

                @include('city.listings.list-availability')

            </div>

            <div class="pl-0 pl-lg-5 d-none d-lg-block border-left text-center ml-auto">

                @include('city.listings.list-rating')

                @include('city.listings.list-price')

            </div>
        </div>

    @endforeach

</div>
