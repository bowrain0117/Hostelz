<section class="py-5 py-lg-6 bg-second">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col-sm-12 text-center">
                <p class="sb-title text-white mb-2 text-left text-lg-center">{{ __('index.NewHorizons') }}</p>
                <h3 class="title-2 text-white mb-0 text-left text-lg-center">{{ __('index.PopularCities') }}</h3>
            </div>
        </div>

        <div id="vue-featured-slider" class="swiper-container featured-slider pb-4 pb-lg-5">
            <slider
                    :data="{{ $featuredCities }}"
                    :city-urls="{{ $cityUrls }}"
                    :lowest-price="{{ $featuredCities->pluck('lowestDormPrice', 'city') }}"
                    :thumbnail="{{ $featuredCities->pluck('thumbnail', 'city') }}"
                    :from="'{{__('bookingProcess.from')}}'">
            </slider>
        </div>
    </div>
</section>

<script src="{{ mix('js/vue/modules/featured-slider.js') }}"></script>