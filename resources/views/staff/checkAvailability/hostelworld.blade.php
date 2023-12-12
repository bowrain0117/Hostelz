@if($hostelworldAvailability->isNotEmpty())
    <p>
        <button class="btn btn-primary" type="button" data-toggle="collapse"
                data-target="#hostelworldAvailability" aria-expanded="false" aria-controls="collapseExample">
            Hostelworld Availability
            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        </button>
    </p>

    <div class="collapse" id="hostelworldAvailability">
        <div class="card card-body">
            @foreach($hostelworldAvailability as $item)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">name</th>
                        <th scope="col">Price</th>
                        <th scope="col">Type</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($item->roomTypes as $room)
                            <tr>
                                <th>{{ $loop->iteration }}</th>
                                <td>{{ $room->description }}</td>
                                <td>
                                    @foreach($room->availability as $date => $availability)
                                        {{ $date }}
                                        @foreach($availability as $a)
                                            <p>beds: {{ $a->beds }}</p>
                                            <p>prices:</p>
                                            @foreach($a->price as $cur => $p)
                                                <div>{{ $cur }} {{ $p }}</div>
                                                <hr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                </td>
                                <td>{{ $room->basicType }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
@else
    <p>Hostelworld No items</p>
@endif
