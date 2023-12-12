<div>
    <h2 class="font-xl font-bold my-4">When to book a hostel in {{ $_data->subjectName }}?</h2>
    <div py-3>
        <p>Let us have a closer look on some statistics and graphics.</p>
        <ul>
            @if($_data->minMonthData)
                <li>The cheapest months to travel to {{ $_data->subjectName }} is {{ $_data->minMonthData->month->format('F') }}. Hostels in {{ $_data->subjectName }} cost ${{ ceil($_data->minMonthData->priceAverage) }} in {{ $_data->minMonthData->month->format('F') }}.</li>
            @endif

            @if($_data->maxMonthData)
                <li>The most expensive month is {{ $_data->maxMonthData->month->format('F') }} with ${{ ceil($_data->maxMonthData->priceAverage) }}.</li>
            @endif

            <li>Total number of hostels in {{ $_data->subjectName }}: {{ $_data->hostelsCount }}</li>

            @if($_data->partyHostelsCount)
                <li>Total number of Party Hostels in {{ $_data->subjectName }}: {{ $_data->partyHostelsCount }}</li>
            @endif

            @if($_data->topRatedCount)
                <li>Total number of hostels in {{ $_data->subjectName }} with a +8.5 top-rating: {{ $_data->topRatedCount }}</li>
            @endif

            @if($_data->mostRatingNeighborhood)
                <li>Most hostels are located in the neighborhoods of {{ $_data->mostRatingNeighborhood }}.</li>
            @endif

        </ul>
    </div>
</div>