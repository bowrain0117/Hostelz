@if ($listings->isNotEmpty())
    <div class="vue-listings-row-slider">
        <slider
                :listings="{{ $listings }}"
                :city-more="'{{ __('city.more') }}'"
        ></slider>
    </div>

    <script src="{{ mix('js/vue/modules/listings-row-slider.js')}}"></script>
@endif
