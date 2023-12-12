{{--
    Input:
        $cityComments - Array or collection of App\Models\CityComment objects.
--}}

<div class="vue-comments-slider">
    <slider :data="{{ $cityComments }}"></slider>
</div>