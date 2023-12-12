<div class="faq-wrap mb-3 mb-lg-5 pb-3 pb-lg-5 border-bottom">

    @section('headerJsonSchema')
        {!! $schema->toScript() !!}
    @stop

    <h2 class="sb-title cl-text d-none d-lg-block"
        id="faq">{!! langGet('city.FAQTitle', [ 'city' => $cityInfo->translation()->city]) !!}</h2>

    <p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed"
       data-toggle="collapse" href="#faq-content">
        {!! langGet('city.FAQTitle', [ 'city' => $cityInfo->translation()->city]) !!}
        <i class="fas fa-angle-down float-right"></i>
        <i class="fas fa-angle-up float-right"></i>
    </p>

    <div class="mt-3 collapse d-lg-block" id="faq-content">
        <p class="tx-small">{!! langGet('city.FAQText', [ 'city' => $cityInfo->translation()->city]) !!}</p>

        <div class="row">
            <div class="col-12">
                <div id="vueFaqs">
                    <Faqs :faqs="{{ $faqs->toJson() }}"/>
                </div>
            </div>
        </div>
    </div>
</div>