<section class="py-5 py-lg-6 bg-light">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col-sm-12 text-center">
                <div class="sb-title cl-primary mb-2 text-left text-lg-center">{{ $subtitle }}</div>
                <h3 class="title-2 cl-dark mb-0 text-left text-lg-center">{{ $title }}</h3>
            </div>
        </div>

        <div class="row row-cols-5 mx-n2">
            @foreach($listings as $listing)
                <div class="px-2 d-flex w-100 mb-4 mb-lg-0">
                    <div class="hostel-card bg-white w-100">
                        <div class="hostel-card-img-wrap">

{{--                            <picture class="w-100">--}}
{{--                                <source media = "(max-width:450px)" data-srcset="{{ $listing->thumbnailURL('webp_thumbnail') }}" type="image/webp">--}}
{{--                                <source media = "(max-width:450px)" data-srcset="{{ $listing->thumbnailURL('thumbnail') }}" type="image/jpeg">--}}
{{--                                <source media = "(max-width:992px)" data-srcset="{{ $listing->thumbnailURL('webp_medium') }}" type="image/webp">--}}
{{--                                <source media = "(max-width:992px)" data-srcset="{{ $listing->thumbnailURL('medium') }}" type="image/jpeg">--}}
{{--                                <source media = "(min-width:993px)" data-srcset="{{ $listing->thumbnailURL('webp_thumbnail') }}" type="image/webp">--}}
{{--                                <source media = "(min-width:993px)" data-srcset="{{ $listing->thumbnailURL('thumbnail') }}" type="image/jpeg">--}}
{{--                                <img class="lazyload blur-up w-100"--}}
{{--                                     src="{{ $listing->thumbnailURL('tiny') }}"--}}
{{--                                     data-src="{{ $listing->thumbnailURL('thumbnail') }}"--}}
{{--                                     alt="{{ $listing->name }}">--}}
{{--                            </picture>--}}

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

                            @if ($listing->cityAlt != '')
                                <div class="my-1 pre-title">
                                    @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                                    {{{ $listing->cityAlt }}}
                                </div>
                            @endif

                            <div class="tx-small my-2">
                                @if ($listing->snippetFull)
                                    {!! wholeWordTruncate($listing->snippetFull, 100) !!}
                                @else
                                    {{ $listing->address }} ...
                                @endif
                            </div>

                            <div class="my-2">
                                <div class="pre-title">@langGet('bookingProcess.from') </div>
                                <span class="tx-body font-weight-bold cl-text">$15.60</span>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>