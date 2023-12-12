<div>
    <h2 id="hostelprices" class="font-xl font-bold my-4">How much are hostels in {{ $subjectName }}?</h2>

    <p>Let us talk about prices. Here is a graph showing you average prices for a bed in a dorm and for a private room. Simply mouse-over to see rates for each month.</p>
    <p>Prices can vary a lot, especially on high-season, weekends, and special holidays such as New Years Eve.</p>

    <div class="row">
        <div class="col-12 col-lg-6">
            <h3 id="dormprices" class="my-4 h4">Average Dorm Price per Month in {{ $subjectName }}</h3>

            <x-graph-price-month
                    id="pricePerMonthDorm"
                    :labels="$pricePerMonth->dorm['labels']"
                    :data="$pricePerMonth->dorm['data']"
                    label="Average Dorm Price Graph"
            />
        </div>

        <div class="col-12 col-lg-6">
            <h3 id="privateroomprices" class="my-4 h4">Average Private Room Price per Month in {{ $subjectName }}</h3>

            <x-graph-price-month
                    id="pricePerMonthPrivate"
                    :labels="$pricePerMonth->private['labels']"
                    :data="$pricePerMonth->private['data']"
                    label="Average Private Price Graph"
            />
        </div>
    </div>
</div>