<section class="hero-home bg-cover shadowed-content"  style="background-image: url({!! routeURL('images', 'best-hostel-price-comparison.jpg') !!})">
    <div class="container text-white z-index-20 py-5 py-lg-6">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="title-1 text-left text-lg-center mb-2 mb-lg-3 text-white">@langGet('index.indextitle')</h1>
                    <p class="sb-title text-left text-lg-center text-white mb-0 mb-lg-5">@langGet('index.indextitlesub')</p>
                </div>
            </div>

            <div class="col-md-12 col-lg-12 col-xl-12 d-none d-lg-block">
                <form action="{!! routeURL('search') !!}" method="get" target="_top" id="heroSearch" class="heroSearchSmall">
                    <div class="search-bar-items" id="date-search-container">
                        <div class="search-bar-item input-icon input-search" id="locationHero">
                            <input type="text" name="searchHero" class="websiteIndexSearch form-control" placeholder="{{{ langGet('index.EnterAName') }}}" required>

                            <div class="spinner-wrap mt-3 ml-5 d-none-i">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>

                        <div class="search-form-index">
                            @include('bookings/_searchFormIndex', [ 'pageType' => 'city' ])
                        </div>

                        <div class="search-bar-item">
                            <button type="submit" class="btn bg-primary text-white btn-block mt-2 mt-sm-0 text-nowrap"><i class="fa fa-search mr-2"></i>@langGet('global.Search')</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>
