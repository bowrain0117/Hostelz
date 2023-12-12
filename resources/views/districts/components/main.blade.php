<div>

    @include('city.banner')

    @if(is_null($listingsData))
        @include('bookings/_search', [ 'pageType' => 'city' ])
    @else
        <section class="container mb-5 mb-lg-6">
            <div id="listingsSearchResult">
                @include('city.listingsList', $listingsData)
            </div>
        </section>
    @endif

    {{--   from view: listings.listingsRowSlider    --}}
    @if($cityInfo->hostelCount > 0)
        <section id="loadExploreSection">
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </section>
    @endif

    <x-district.neighborhood :cityName="$district->city->city" :items="$district->neighborhoods"/>

    <section class="bg-white py-5 py-lg-6">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-8 pr-lg-6">

                    <div>
                        {!! $district->description !!}
                    </div>

                    <x-faqs :$faqs :city="$district->city->city"/>

                </div>

                <div class="col-12 col-lg-4">

                    <x-slp.categories-sidebar :$cityInfo :$cityCategories />

                </div>
            </div>
        </div>
    </section>
</div>