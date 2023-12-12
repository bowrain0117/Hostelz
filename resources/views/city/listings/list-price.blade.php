@php use App\Booking\RoomAvailability; @endphp
@if (!empty($bestAvailabilityByListingID))
    @php
        /** @var RoomAvailability $availability */
        $availability = $bestAvailabilityByListingID[$listing->id];
    @endphp
    <a href="{{ $availability->bookingPageLink() }}"
       class="btn btn-danger d-flex ml-auto ml-md-0 rounded-sm"
       title="book now {{ $listing->name }}" target="_blank"
       onclick="ga('send','event','City','book now {{ $listing->name }}', '{!! $listing->city !!}')"
       rel="nofollow"
    >
        Book Now
    </a>
@endif