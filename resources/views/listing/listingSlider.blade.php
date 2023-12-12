@if($listings->isNotEmpty())
    <section class="pb-3 mb-3 border-bottom" @isset($blockId) id="{{ $blockId }}" @endisset>
        <div class="container">
            <div class="row ">
                <div class="col-12">
                    <h4 class="sb-title cl-text mb-3">@langGet('listingDisplay.MoreHostelsYouMightLike')</h4>

                    @include('listings.slider', [ 'listings' => $listings, ])

                </div>
            </div>
        </div>
    </section>
@endif
