<div>
    <div class="listingsList pt-3">
        <div class="row vue-full-card-slider-wrap">
            @foreach($listings as $listing)
                <div class="col-lg-12 mb-5 listing listingLink listing">

                    <h2 id="{{ $listing['idAttr'] }}" class="card-title h3">
                        <span class="icon-rounded bg-dark mb-3 text-white icon-rounded-sm">
                            {{ $loop->iteration }}
                        </span>
                        {{ $listing['name'] }}
                    </h2>

                    <p class="article-metadata">
                        @if($listing['minPrice'])
                            <span class="mr-4"><i class="fa fa-bed"></i> Rooms from {{ $listing['minPrice'] }}</span>
                        @endif

                        <span class="mr-4"><i class="fa fa-star text-warning"></i> Rating: <span
                                    class="font-weight-bold">{{ $listing['rating'] }}</span></span>
                    </p>

                    @if($listing['specialText'])
                        <div class="mb-3">{!! nl2p($listing['specialText']) !!}</div>
                    @endif

                    <x-slp::compare-prices :name="$listing['name']" :url="$listing['url']"/>

                    <x-slp.partials.edit-links :listing="$listing['model']" :category="$slp->category"/>

                    <x-slp::ota-links :listing="$listing" :otaLinks="$listing['otaLinks']"/>

                    <x-sliders.card-slider :listing="$listing['model']"/>

                </div>
            @endforeach
        </div>
    </div>

    <x-slp.map :$slp/>

    <x-slp.seo-table :$slp/>

</div>