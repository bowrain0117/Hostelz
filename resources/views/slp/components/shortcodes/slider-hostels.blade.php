@php
    if($listings->isEmpty()) {
        return;
    }
@endphp

<div>
    <div class="vue-listings-slp-slider">
        <slider :listings="{{ $listings }}"></slider>
    </div>
</div>