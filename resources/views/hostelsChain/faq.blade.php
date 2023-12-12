<section class="py-5 py-lg-6">
    <div class="row justify-content-around text-center mb-5">
        <div class="col-8">
            <div class="faq-wrap">
                <h2 class="sb-title cl-text d-none d-lg-block" id="faq">{!! langGet('city.FAQTitle', [ 'city' => $hostelChain->name]) !!}</h2>

                <p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed" data-toggle="collapse" href="#faq-content">
                    {!! langGet('city.FAQTitle', [ 'city' => $hostelChain->name]) !!}
                    <i class="fas fa-angle-down float-right"></i>
                    <i class="fas fa-angle-up float-right"></i>
                </p>

                <div class="mt-5 collapse d-lg-block" id="faq-content">
                    <div class="row">
                        <div class="col-12">
                            <div id="accordion" role="tablist">
                                @foreach ($FAQs as $faq)
                                    <div class="card border-0 mb-4 pb-2">
                                        <div id="heading{{ $loop->index }}" role="tab" class="tx-body font-weight-600">
                                            <a data-toggle="collapse" href="#collapse{{ $loop->index }}" aria-expanded="false" aria-controls="collapseOne" class="accordion-link collapsed cl-text py-0 collapse-arrow-wrap collapsed">
                                                {!! $faq['question'] !!}
                                                <i class="fas fa-angle-down float-right"></i>
                                                <i class="fas fa-angle-up float-right"></i>
                                            </a>
                                        </div>
                                        <div id="collapse{{ $loop->index }}" role="tabpanel" aria-labelledby="heading{{ $loop->index }}" data-parent="#accordion" class="collapse mt-2">
                                            <div class="tx-body cl-text text-content">{!! $faq['answer'] !!}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>