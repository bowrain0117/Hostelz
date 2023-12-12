@php use App\Enums\CategorySlp; @endphp

<footer class="position-relative z-index-10 bg-second py-5 py-lg-6 text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-12 mb-4 mb-md-0">
                <h6 class="text-uppercase tx-body text-white font-weight-bold mb-2">@langGet('global.FooterTitle2')</h6>
                <ul class="list-unstyled mb-0">
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('articles', 'when-to-book-hostels') !!}"
                                               class="text-white tx-small"
                                               title="@langGet('global.FooterBox2Link1')">@langGet('global.FooterBox2Link1')</a>
                    </li>
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('articles', 'what-to-pack') !!}"
                                               class="text-white tx-small"
                                               title="@langGet('global.FooterBox2Link2')">@langGet('global.FooterBox2Link2')</a>
                    </li>
                    <li class="pb-2 p-md-0 pb-md-3"><a href="{!! routeURL('articles', 'are-hostels-safe') !!}"
                                                       class="text-white tx-small"
                                                       title="@langGet('global.FooterBox2Link4')">@langGet('global.FooterBox2Link4')</a>
                    </li>
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('articles') !!}" class="text-white tx-small"
                                               title="@langGet('global.AllArticlesBlog') Hostelz">@langGet('global.AllArticlesBlog')</a>
                    </li>
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('articles', 'best-hostel-tips-backpacking') !!}"
                                               class="text-white tx-small"
                                               title="@langGet('global.ExclusiveContent')">@langGet('global.ExclusiveContent')
                            <span class="badge badge-primary ml-1">@langGet('global.Pluz')</span></a></li>
                </ul>
                <hr class="my-5 bg-light-accent d-lg-none">
            </div>

            <div class="col-lg-4 col-12 mb-4 mb-md-0">
                <h6 class="text-uppercase tx-body text-white font-weight-bold mb-2">@langGet('global.FooterTitle3')</h6>
                <ul class="list-unstyled mb-0">
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('allContinents') !!}" class="text-white tx-small"
                                               title="{{ __('global.Hostels') }}">{{ __('global.Hostels') }}</a></li>
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('howCompareHostelPrices') !!}"
                                               class="text-white tx-small"
                                               title="@langGet('global.HowToCompare')">@langGet('global.HowToCompare')</a>
                    </li>
                    <li class="pb-2 p-md-0"><a href="{!! routeURL('faq') !!}" class="text-white tx-small" title="Help">Help</a>
                    </li>
                    @if(!auth()->check())
                        <li class="pb-2 p-md-0"><a href="{!! routeURL('userSignup') !!}"
                                                   class="text-white tx-small js-show-if-not-login"
                                                   title="@langGet('global.SignUp')">@langGet('global.SignUp')</a></li>
                    @endif
                </ul>
                <hr class="my-5 bg-light-accent d-lg-none">
            </div>

            <div class="col-lg-4 col-12 mb-4 mb-md-0">
                <h6 class="text-uppercase tx-body text-white font-weight-bold mb-2">Brand New For You</h6>
                <ul class="list-unstyled mb-0">
                    <li class="pb-2 p-md-0">
                        <a href="{!! routeURL('comparison') !!}" class="text-white tx-small"
                           title="Comparizon Tool">Comparizon Tool</a>
                    </li>
                    <li class="pb-2 p-md-0">
                        <a href="{!! routeURL('slp.index.' .CategorySlp::Best->value) !!}"
                           class="text-white tx-small" title="Best Hostel Guides">Best Hostel
                            Guides</a>
                    </li>
                    <li class="pb-2 p-md-0">
                        <a href="{!! routeURL('slp.index.' .CategorySlp::Private->value) !!}"
                           class="text-white tx-small" title="Hostels with Private Rooms">Hostels
                            with Private Rooms</a>
                    </li>
                    <li class="pb-2 p-md-0">
                        <a href="{!! routeURL('slp.index.' .CategorySlp::Party->value) !!}"
                           class="text-white tx-small" title="Hostels with Private Rooms">Party
                            Hostels</a>
                    </li>
                    <li class="pb-2 p-md-0">
                        <a href="{!! routeURL('slp.index.' .CategorySlp::Cheap->value) !!}"
                           class="text-white tx-small" title="Cheapest Hostels">Cheapest Hostels</a>
                    </li>
                </ul>
                <hr class="my-5 bg-light-accent d-lg-none">
            </div>
        </div>
        <hr class="my-5 bg-light-accent d-none d-lg-block">
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-6 mb-4 mb-lg-0 mt-2">
                <ul class="list-inline tx-small mb-0">
                    <li class="list-inline-item">
                        <a href="https://www.facebook.com/hostelz" target="_blank" title="Facebook Hostelz"
                           class="text-white text-hover-primary">
                            @include('partials.svg-icon', ['svg_id' => 'facebook', 'svg_w' => '24', 'svg_h' => '25'])
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://www.instagram.com/hostelz/" target="_blank" title="Instagram Hostelz"
                           class="text-white text-hover-primary">
                            @include('partials.svg-icon', ['svg_id' => 'instagram', 'svg_w' => '24', 'svg_h' => '25'])
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://youtube.com/@Hostelz" target="_blank" title="Youtube Hostelz"
                           class="text-white text-hover-primary">
                            @include('partials.svg-icon', ['svg_id' => 'youtube', 'svg_w' => '24', 'svg_h' => '25'])
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://pinterest.com/hostelz/" target="_blank" title="Pinterest Hostelz"
                           class="text-white text-hover-primary">
                            @include('partials.svg-icon', ['svg_id' => 'pinterest', 'svg_w' => '24', 'svg_h' => '25'])
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-4 col-12 order-1 order-lg-0">
                <ul class="pl-0 text-left text-lg-center text-white tx-small">
                    <li class="list-inline-item mr-2">
                        <a href="{!! routeURL('about') !!}" class="text-white"
                           title="{!! langGet('global.AboutHostelz') !!}">{!! langGet('global.AboutHostelz') !!}</a>
                    </li>
                    ·
                    <li class="list-inline-item mx-2">
                        <a href="{!! routeURL('privacy-policy') !!}" class="text-white"
                           title="{!! langGet('privacy.PageTitle') !!}">{!! langGet('privacy.PageTitle') !!}</a></li>
                    ·
                    <li class="list-inline-item mx-2">
                        <a href="{!! routeURL('termsConditions') !!}" class="text-white"
                           title="{!! langGet('privacy.TCsTitle') !!}">{!! langGet('privacy.TCsTitle') !!}</a></li>
                    ·
                    <li class="list-inline-item ml-2">
                        <a href="{!! routeURL('contact-us') !!}" class="text-white"
                           title="{!! langGet('global.ContactHostelz') !!}">{!! langGet('global.ContactHostelz') !!}</a>
                    </li>
                </ul>
                <div class=" text-left text-lg-center tx-small">{!! langGet('global.Copyright', [ 'year' => date('Y') ]) !!}</div>
            </div>

            <div class="col-lg-4 col-6 mb-4 mb-lg-0">
                <ul class="list-inline mb-0 mt-md-0 text-center text-md-right text-sm text-white">

                    @includeWhen(0, 'partials.footer._langSelectFooter')

                </ul>
            </div>
        </div>
    </div>

    <button id="toTop" class="toogleOnScroll"><i class="fa fa-arrow-up"></i></button>

</footer>
<!-- /Footer end-->


@include('partials.userBottomMenu')

<script>var basePath = ''</script>
