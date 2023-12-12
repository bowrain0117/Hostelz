@if($bookingAvailability->isNotEmpty())
    <p>
        <button class="btn btn-primary" type="button" data-toggle="collapse"
                data-target="#bookingAvailability" aria-expanded="false" aria-controls="collapseExample">
            booking.com Availability
            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        </button>
    </p>
    <div class="collapse" id="bookingAvailability">
        <div class="card card-body">
            @php
                $blocks = $bookingAvailability->pluck('block');
            @endphp

            @if($blocks->isNotEmpty())
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">name</th>
                        <th scope="col">Price</th>
                        <th scope="col">Taxes</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($blocks->first() as $block)
                        <tr>
                            <th>{{ $loop->iteration }}</th>
                            <td>{{ $block->name }}</td>
                            <td> @dump($block->min_price)</td>
                            <td>{{ $block->taxes }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@else
    <p>booking.com No items</p>
@endif
