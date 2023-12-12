<div class="d-flex justify-content-between align-items-end mb-5">
    <h1 class="hero-heading mb-0 h2">Upcoming Reservationz</h1>
</div>

<div class="d-flex justify-content-between align-items-center flex-column flex-lg-row mb-5">
    <div class="mr-3">
        <p class="mb-3 mb-lg-0">Track your reservations from Hostelworld and Booking.com to unlock special travel guides and more.</p>
    </div>
</div>

<div class="list-group shadow mb-6">
    @forelse($bookedReservations as $reservation)
        <x-user-reservations::reservations-row>
            <x-user-reservations::hostel-image :$reservation>
                <p class="text-xs mt-3">
                    Need to change your reservation?
                    <a href="{{ $reservation->otaLink }}" rel="nofollow" target="_blank">Get in touch with {{ $reservation->otaName }}.</a>
                </p>
            </x-user-reservations::hostel-image>

            <x-user-reservations::hostel-info :$reservation>
                <p class="text-sm text-gray-600">You booked with: {{ $reservation->otaName }}</p>
            </x-user-reservations::hostel-info>

            <x-user-reservations::hostel-guide :$reservation/>
        </x-user-reservations::reservations-row>
    @empty
        <div class="list-group-item list-group-item-action p-4">
            <p>No reservations yet.</p>
            <div class="pt-4 text-center">
                <button class="btn-lg btn-primary mt-2 mt-sm-0 js-open-search-location"><i class="fa fa-search mr-1 mr-md-3"></i> Find your next Hostel Adventure here</button>	
            </div>
        </div>
    @endforelse
</div>

<div class="mb-5">
    <h2 class="hero-heading mb-0 h3">Blast from the Past Reservationz</h2>
</div> 

<div class="list-group shadow mb-6">
    @forelse($stayedReservations as $reservation)
        <x-user-reservations::reservations-row>
            <x-user-reservations::hostel-image :$reservation class="opacity-4"/>

            <x-user-reservations::hostel-info :$reservation :active="false" class="text-gray-600">
                <p class="text-sm text-gray-600">You booked with: {{ $reservation->otaName }}</p>

                @if($reservation->leaveReviewLink)
                    <h4 class="sb-title cl-text mb-3">Your Review Matters!</h4>
                    <p class="text-sm">Help others on their journey by sharing insights from your recent
                        stay.</p>
                    <a href="{{ $reservation->leaveReviewLink }}" name="mode" value="insert"
                       class="btn btn-lg m-auto px-5 tt-n btn-block btn-warning">
                        Leave a Review
                    </a>
                @endif
            </x-user-reservations::hostel-info>

            <x-user-reservations::hostel-guide :$reservation class="opacity-4"/>
        </x-user-reservations::reservations-row>
    @empty
        <div class="list-group-item list-group-item-action p-4">
            <p>No reservations yet.</p>
        </div>
    @endforelse
</div>