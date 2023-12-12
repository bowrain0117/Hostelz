<div>
    <div class="listingsList pt-3">
        <div class="row vue-full-card-slider-wrap">
            @foreach($listings as $listing)
                <div class="col-lg-12 mb-5 listing listingLink listing">
                    <h2 id="{{ $listing['idAttr'] }}" class="card-title h3">
                        <span class="icon-rounded bg-dark mb-3 text-white icon-rounded-sm">
                            {{ $loop->iteration }}
                        </span> {{ $listing['name'] }}
                    </h2>

                    <div class="article-metadata d-flex flex-row">
                        <div class="" style="flex: 1 1 0px;">
                            <h5>Quick Overview:</h5>

                            <p class="mr-4"><x-check-icon-2 checked="true" /> Rating: {{ $listing['rating'] }}</p>
                            @if($listing['distance'])
                            <p class="mr-4"><x-check-icon-2 checked="true" /> {{ $listing['distance'] }} km to city center</p>
                            @endif
                            <p class="mr-4"><x-check-icon-2 checked="{{ $listing['breakfast'] }}" /> Breakfast {{ $listing['breakfast'] ? '': 'not' }} included</p>

                        </div>
                        <div class="" style="flex: 1 1 0px;">
                            <h5>Prices</h5>
                            @if($listing['price']['dorm'])
                                <p class="mr-4"><i class="fa fa-bed"></i> Dorm from {{ $listing['price']['dorm'] }} per night</p>
                            @endif
                            @if($listing['price']['private'])
                                <p class="mr-4"><i class="fa fa-bed"></i> Rooms from {{ $listing['price']['private'] }} per night</p>
                            @endif

                        </div>
                    </div>

                    @if($listing['specialText'])
                        <div class="mb-3">{!! nl2p($listing['specialText']) !!}</div>
                    @endif

                    <x-slp::compare-prices :name="$listing['name']" :url="$listing['url']" />

                    <x-slp.partials.edit-links :listing="$listing['model']" :category="$slp->category" />

                    <x-slp::ota-links :listing="$listing" :otaLinks="$listing['otaLinks']" />

                    <x-sliders.card-slider :listing="$listing['model']" />

                </div>
            @endforeach
        </div>
    </div>

    <x-slp.map :$slp />

    <x-slp.seo-table :$slp />

</div>