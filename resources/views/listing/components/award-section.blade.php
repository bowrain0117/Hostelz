<section class="container">
    <div class="border-bottom mb-5 pb-5 text-break" id="hostel-awards">

        <h3 class="sb-title cl-text mb-5">Awards &amp; Features</h3>

        <div class="text pb-3 mb-3 pb-lg-3 mb-lg-3 text-break">{{ $listing->name }} is a popular hostel in {{ $listing->city }}. It's being featured in ....
        </div>

        <div class="row mb-5">

            @foreach($items as $item)
                <x-partials.cards.card :$item />
            @endforeach

        </div>
    </div>
</section>
