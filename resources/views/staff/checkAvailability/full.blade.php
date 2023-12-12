@if($fullAvailability->isNotEmpty())
    <p>
        <button class="btn btn-success" type="button" data-toggle="collapse"
                data-target="#fullAvailability" aria-expanded="false" aria-controls="collapseExample">
            Full Availability From All OTA
            <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
        </button>
    </p>
    <div class="collapse" id="fullAvailability">
        <div class="card card-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">name</th>
                    <th scope="col">Price</th>
                    <th scope="col">System</th>
                </tr>
                </thead>
                <tbody>
                @foreach($fullAvailability as $item)
                    <tr>
                        <th scope="row">{{ $loop->iteration }}</th>
                        <td>{{ $item->roomInfo->name }}</td>
                        <td>
                            @foreach($item->availabilityEachNight as $i)
                                <div>
                                    blocksAvailable:{{ $i['blocksAvailable'] }}
                                </div>
                                <div class="mb-3">
                                    pricePerBlock: {{$i['pricePerBlock']->getValue()}}
                                </div>
                            @endforeach

                            @dump($item->availabilityEachNight)
                        </td>
                        <td>{{ $item->imported->system }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>




@else
    <p>Full Availability No items</p>
@endif