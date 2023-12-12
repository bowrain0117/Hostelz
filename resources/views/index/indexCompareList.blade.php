<section class="py-5 py-lg-6 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <p class="sb-title text-primary mb-2 text-left text-lg-center">{!! langGet('index.compareForYouTitle') !!}</p>
            <h3 class="title-2 cl-dark mb-0 text-left text-lg-center">{!! langGet('index.compareForYouDesc') !!}</h3>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-around text-center mb-5">
            <div class="col-12 col-lg-6 d-flex justify-content-between">
                <div class="hover-animate d-flex align-items-center">
                    <span class="d-none d-lg-inline">@include('partials.svg-icon', ['svg_id' => 'hostelworld-logo', 'svg_w' => '120', 'svg_h' => '27'])</span>
                    <span class="d-inline d-lg-none">@include('partials.svg-icon', ['svg_id' => 'hostelworld-logo-2', 'svg_w' => '96', 'svg_h' => '22'])</span>
                </div>

                <div class="hover-animate d-flex align-items-center">
                    <span class="d-none d-lg-inline">@include('partials.svg-icon', ['svg_id' => 'booking-logo', 'svg_w' => '120', 'svg_h' => '25'])</span>
                    <span class="d-inline d-lg-none">@include('partials.svg-icon', ['svg_id' => 'booking-logo-2', 'svg_w' => '97', 'svg_h' => '17'])</span>
                </div>

                <div class="hover-animate d-flex align-items-center">
                    <span class="d-none d-lg-inline">@include('partials.svg-icon', ['svg_id' => 'hostelclub-logo', 'svg_w' => '130', 'svg_h' => '40'])</span>
                    <span class="d-inline d-lg-none">@include('partials.svg-icon', ['svg_id' => 'hostelclub-logo', 'svg_w' => '100', 'svg_h' => '30'])</span>


{{--                    @include('partials.svg-icon', ['svg_id' => 'hostelclub-logo', 'svg_w' => '120', 'svg_h' => '15'])--}}
                </div>

            </div>
        </div>

        <div class="row justify-content-around text-center mb-5">
            <div class="col-12 col-8">
                <iframe loading="lazy" width="560" height="315"
                    class="lazyload"
                    data-expand="20"
                    data-src="https://www.youtube.com/embed/y0OrM1InNjs"
                    src="" frameborder="0"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
    	</div>

        <div class="row justify-content-center">
            <div class="col-12 text-center">
                <a href="{{ routeURL( 'howCompareHostelPrices' ) }}" title="Learn more" target="" class="btn btn-primary btn-lg full-width rounded px-4 px-sm-5" onclick="ga('send','event','Index','details', 'Learn more')">Learn more</a>
            </div>
        </div>
    </div>
</section>
