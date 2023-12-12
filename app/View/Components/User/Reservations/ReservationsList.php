<?php

namespace App\View\Components\User\Reservations;

use App\Enums\StatusBooking;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ReservationsList extends Component
{
    public Collection $reservations;

    public function __construct(
        protected User $user,
    ) {
        $this->reservations = $user->bookings()
            ->orderBy('bookingTime', 'desc')
            ->orderBy('startDate', 'desc')
            ->get();
    }

    public function render(): View
    {
        return view('user.components.reservations.reservations-list');
    }

    public function bookedReservations(): Collection
    {
        return $this->reservations
            ->where('status', StatusBooking::Booked)
            ->map($this->getData(...));
    }

    public function stayedReservations(): Collection
    {
        return $this->reservations
            ->where('status', StatusBooking::Stayed)
            ->map($this->getData(...));
    }

    protected function getData(Booking $booking)
    {
        return (object) [
            'hostelName' => $booking->listing->name,
            'hostelImage' => $booking->listing->thumbnail,
            'hostelLink' => $booking->listing->path,
            'hostelCity' => $booking->listing->city,
            'hostelCountry' => $booking->listing->country,
            'startDate' => $booking->startDate->toFormattedDateString(),
            'endDate' => $booking->startDate->addDays((int) $booking->nights)->toFormattedDateString(),
            'otaName' => $booking->getImportSystem()?->shortName(),
            'otaLink' => $booking->imported->importService::getDefaultLinkRedirect(
                'reservations' //getCMPLabel('reservations', $booking->listing->city)
            ),
            'leaveReviewLink' => $booking->getAfterBookingRatingLink(),
        ];
    }
}
