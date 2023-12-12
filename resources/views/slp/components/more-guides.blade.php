<section>
    <div class="border-bottom my-5 py-5 text-break" id="hostel-awards">

        <h3 class="sb-title cl-text mb-5">More {{ $slp->subjectable->city }} Guides for you</h3>

        <div class="row mb-5">

            @foreach($items as $item)
                <x-partials.cards.card :$item />
            @endforeach

        </div>
    </div>
</section>
