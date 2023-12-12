<?php

namespace App\Livewire\Staff\Listing;

use App\Booking\BookingService;
use App\Booking\SearchCriteria;
use App\Services\ImportSystems\BookHostels\APIBookHostels;
use App\Services\ImportSystems\BookHostels\AvailabilityBookHostels;
use App\Services\ImportSystems\BookingDotCom\APIBookingDotCom;
use App\Services\ImportSystems\BookingDotCom\AvailabilityBookingDotCom;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class CheckAvailability extends Component
{
    public $listing;

    public $startDate;

    public $nights;

    public $people;

    public $roomType;

    public $currency;

    public $guestCountryCode;

    private Collection $fullAvailability;

    private Collection $bookingAvailability;

    private Collection $hostelworldAvailability;

    private array|object $bookingHotelAvailability;

    private ?SearchCriteria $searchCriteria = null;

    private array $options = [];

    public function mount(): void
    {
        $this->fill([
            'startDate' => now()->format('Y-m-d'),
            'nights' => 1,
            'people' => 1,
            'roomType' => 'dorm',
            'currency' => 'EUR',
            'guestCountryCode' => 'ES',

            'hostelworldAvailability' => collect(),
            'bookingAvailability' => collect(),
            'fullAvailability' => collect(),
        ]);
    }

    public function search()
    {
        $this->searchCriteria = $this->getSearchCriteria();
        $this->options = $this->getOptions();
        $this->fullAvailability = $this->getFullAvailability();
        $this->bookingAvailability = $this->getBookingAvailability();
        $this->bookingHotelAvailability = $this->getBookingHotelAvailability();
        $this->hostelworldAvailability = $this->getHostelworldAvailability();
    }

    public function getFullAvailability(): Collection
    {
        return collect(BookingService::getAvailabilityForListing($this->listing, $this->searchCriteria, false));
    }

    public function getBookingAvailability(): Collection
    {
        $importeds = $this->listing->importeds()->where([['system', 'BookingDotCom']])->get();

        return APIBookingDotCom::doBlockAvailabilityReqest(
            $this->options,
            $importeds->chunk((new AvailabilityBookingDotCom())::ITEMS_COUNT_IN_ONE_POOL)
        );
    }

    public function getBookingHotelAvailability()
    {
        $importeds = $this->listing->importeds()->where([['system', 'BookingDotCom']])->get();

        if ($importeds->isEmpty()) {
            return [];
        }

        return APIBookingDotCom::doRequest(
            useSecureSite: false,
            method: 'hotelAvailability',
            options: [
                'checkin' => $this->startDate,
                'checkout' => Carbon::createFromFormat('Y-m-d', $this->startDate)
                                    ->addDays($this->nights)
                                    ->format('Y-m-d'),
                'hotel_ids' => $importeds->first()->intCode,
                'room1' => 'A',
                'extras' => 'hotel_details,add_cheapest_breakfast_rate,block_payment_options,hotel_amenities,payment_terms,room_amenities,room_details,room_policies,sustainability',
                'guest_country' => $this->guestCountryCode,
            ]
        );
    }

    public function getHostelworldAvailability(): Collection
    {
        $importeds = $this->listing->importeds()->where([['system', 'BookHostels']])->get();

        $chunk = $importeds->chunk(AvailabilityBookHostels::CHUNK_COUNT);

        return APIBookHostels::getAvailabilityForImporteds(
            $chunk,
            (new AvailabilityBookHostels())->getOptions($this->searchCriteria)
        );
    }

    public function getOptions()
    {
        return (new AvailabilityBookingDotCom())->getOptions(
            $this->searchCriteria,
            $this->guestCountryCode,
            false
        );
    }

    public function getSearchCriteria(): SearchCriteria
    {
        return new SearchCriteria(
            [
                'startDate' => Carbon::createFromFormat('Y-m-d', $this->startDate),
                'nights' => $this->nights,
                'people' => $this->people,
                'roomType' => $this->roomType,
                'currency' => $this->currency,
                'language' => 'en',
            ]
        );
    }

    public function render()
    {
        return view(
            'livewire.staff.listing.check-availability',
            [
                'searchCriteria' => $this->searchCriteria,
                'options' => $this->options,
                'fullAvailability' => $this->fullAvailability ?? collect(),
                'bookingAvailability' => $this->bookingAvailability ?? collect(),
                'bookingHotelAvailability' => $this->bookingHotelAvailability ?? collect(),
                'hostelworldAvailability' => $this->hostelworldAvailability ?? collect(),
            ]
        );
    }
}
