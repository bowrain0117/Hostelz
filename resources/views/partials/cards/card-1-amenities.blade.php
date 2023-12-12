<div data-listing-id="{{ $listing->id }}" class="col-lg-4 mb-5 hover-animate h-auto">
    <div class="hostel-card bg-light">
        <div class="hostel-card-img-wrap position-relative">

            {{--        <picture class="w-100">
                        <source media = "(max-width:450px)" data-srcset="{{ $listing->thumbnailURL('webp_thumbnail') }}" type="image/webp">
                        <source media = "(max-width:450px)" data-srcset="{{ $listing->thumbnailURL('thumbnail') }}" type="image/jpeg">
                        <source media = "(max-width:992px)" data-srcset="{{ $listing->thumbnailURL('webp_medium') }}" type="image/webp">
                        <source media = "(max-width:992px)" data-srcset="{{ $listing->thumbnailURL('medium') }}" type="image/jpeg">
                        <source media = "(min-width:993px)" data-srcset="{{ $listing->thumbnailURL('webp_thumbnail') }}" type="image/webp">
                        <source media = "(min-width:993px)" data-srcset="{{ $listing->thumbnailURL('thumbnail') }}" type="image/jpeg">
                        <img class="lazyload blur-up w-100"
                             src="{{ $listing->thumbnailURL('tiny') }}"
                             data-src="{{ $listing->thumbnailURL('thumbnail') }}"
                             alt="{{ $listing->name }}">
                    </picture>--}}

            {{--            <div class="position-absolute top-0 right-0 p-3">@include('wishlist.heart')</div>--}}
            <x-wishlist-icon class="z-index-40" :listing-id="$listing->id"/>

            <img class="hostel-card-img w-100" src="{{ $listing->thumbnailURL() }}" alt="{{ $listing->name }}">

        </div>

        <div class="hostel-card-body p-3">
            <div class="hostel-card-body-header d-flex justify-content-between align-items-stretch">
                <h5 class="hostel-card-title tx-body font-weight-bold mb-0">
                    <a class="cl-text" target="_blank" href="{{ $listing->getURL() }}">{{ $listing->name }}</a>
                </h5>
                <div class="hostel-card-rating hostel-card-rating-small flex-shrink-0">
                    {{ $listing->formatCombinedRating() }}
                </div>
            </div>

            <div class="mb-2">
                @foreach($cardTagTitles as $title)
                    <span class="pre-title listing-feature">{{ $title }}</span>
                @endforeach
            </div>

            @if ($listing->cityAlt != '')
                <div class="my-1 pre-title">
                    @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                    {{{ $listing->cityAlt }}}
                </div>
            @endif

            <div class="my-2">
                <span class="pre-title mr-2">@langGet('bookingProcess.from') </span>
                <span class="tx-body font-weight-bold cl-text">$15.60</span>
            </div>

            <div class="my-2">
                @if ($features = \App\Models\Listing\ListingFeatures::getSoloTravelerFeatures($listing->compiledFeatures))
                    <b>{!! langGet('city.AmenitiesEnjoy') !!}:</b> {{ implode(' · ', $features ) }}
                @endif
            </div>

        </div>
    </div>
</div>
