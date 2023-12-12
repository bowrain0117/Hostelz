<p>
    <button class="btn btn-warning" type="button" data-toggle="collapse"
            data-target="#rawData" aria-expanded="false" aria-controls="collapseExample">
        RAW API DATA
        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
    </button>
</p>
<div class="collapse" id="rawData">
    <div class="card card-body">
        <h3>RAW Availability From All OTA</h3>
        @dump($fullAvailability)

        <h4>raw hostelworld</h4>
        @dump($hostelworldAvailability)

        <h4>raw booking.com</h4>
        @dump($bookingAvailability)

        <h4>raw hotelAvailability booking.com</h4>
        @dump($bookingHotelAvailability)

        <h3>searchCriteria</h3>
        @dump($searchCriteria)

        <h3>API BDC Options</h3>
        @dump($options)
    </div>
</div>