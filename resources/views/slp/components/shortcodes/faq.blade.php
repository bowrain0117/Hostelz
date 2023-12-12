<div>
    @if($faqs->isNotEmpty())
        <x-faqs :$faqs :city="$cityInfo->city"/>
    @else
        <x-city.faq :cityInfo="$cityInfo" :priceAVG="$priceAVG"/>
    @endif
</div>
