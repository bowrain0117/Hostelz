<section class="py-5 py-lg-6 bg-second" @isset($blockId) id="{{ $blockId }}" @endisset>
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col-sm-12 text-center">
                <h3 class="title-2 text-white mb-2 text-left text-lg-center">{{ $title }}</h3>
                <div class="sb-title text-white text-left text-lg-center">{{ $subtitle }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-5">
                @include('listings.sliderBestFor')
            </div>
        </div>
    </div>
</section>