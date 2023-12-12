<?php

namespace App\Services\ImportSystems\BookingDotCom;

use App\Enums\DeviceBooking;
use App\Enums\StatusBooking;
use App\Helpers\EventLog;
use App\Models\Booking;
use App\Models\Imported;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Lib\Currencies;

class MaintenanceBookingDotCom
{
    public function addNewBookings(): string
    {
        $date = Carbon::now()->setTimezone('Europe/Paris');
        $bookingDetails = APIBookingDotCom::doRequest(
            true,
            'bookingDetails',
            [
                'created_from' => with(clone $date)->subDays(1)->format('Y-m-d'),
                'created_until' => $date->format('Y-m-d'),
                'extras' => 'status_details,total_room_nights,no_show',
            ],
            45
        );

        if (! $bookingDetails || ! isset($bookingDetails->result)) {
            throw new \Exception('No result data returned for bookingDetails.');
        }

        $output = '';

        foreach ($bookingDetails->result as $bookingData) {
            if (! is_object($bookingData)) {
                Log::channel('import')->warning("bookingData isn't an object: " . json_encode($bookingData));

                continue;
            }

            if (! isset($bookingData->hotel_id)) {
                Log::channel('import')->warning('hotel id is empty : ' . json_encode($bookingData));

                continue;
            }

            if ((int) $bookingData->affiliate_id !== BookingDotComService::DIRECT_LINK_AFFILIATE_ID) {
//                Log::channel('import')->info("Booking.com booking from another affiliate_id ({$bookingData->affiliate_id})");
                continue;
            }

            if (Booking::where('system', 'BookingDotCom')->where('bookingID', $bookingData->reservation_id)->exists()) {
                continue;
            }

            $imported = Imported::where('system', 'BookingDotCom')->where('intCode', $bookingData->hotel_id)->first();
            if (! $imported) {
                continue;
            }

            $commissionAmount = Currencies::convert($bookingData->euro_fee, 'EUR', 'USD'); // "euro_fee" is the commission we get
            $commissionFraction = $bookingData->fee_percentage / 100;

            $booking = new Booking([
                'system' => 'BookingDotCom',
                'status' => $this->getStatus($bookingData->status),
                'reject_reason' => $this->getRejectedReason($bookingData->status),
                'bookingID' => $bookingData->reservation_id,
                'importedID' => $imported->id,
                'bookingTime' => Carbon::createFromFormat('Y-m-d H:i:s', $bookingData->created, 'Europe/Paris')
                    ->setTimezone(date_default_timezone_get()),
                'startDate' => $bookingData->checkin,
                'endDate' => $bookingData->checkout,
                'nights' => Carbon::createFromFormat('Y-m-d', $bookingData->checkin)
                    ->diffInDays(Carbon::createFromFormat('Y-m-d', $bookingData->checkout)),
                'depositUSD' => $commissionFraction ? $commissionAmount / $commissionFraction : 0, // This is a backwards way to figure out the deposit, but it's the only info we have
                'commission' => $commissionAmount,
                // 'nationality' => guest_country ('es') (is just an abbreviation)
                'email' => $bookingData->booker_email ?? '',
                'firstName' => $bookingData->booker_firstname ?? '',
                'lastName' => $bookingData->booker_lastname ?? '',
                'label' => $bookingData->affiliate_label ?? null,
                'device' => $this->getDevice($bookingData->user_device),
            ]);

            $trackingCode = str($bookingData->affiliate_label)->after('_t_');

            if ($booking->validateAndSave($trackingCode)) {
                $output .= "Saved booking $booking->id. ";
            }
        }

        return $output;
    }

    public function updateStatus(): string
    {
        $date = Carbon::now()->setTimezone('Europe/Paris');
        $bookingDetails = APIBookingDotCom::doRequest(
            true,
            'bookingDetails',
            [
                'last_change' => $date->subDays(2)->format('Y-m-d'),
                'extras' => 'status_details,total_room_nights,no_show',
            ],
            45
        );

        if (! $bookingDetails || ! isset($bookingDetails->result)) {
            throw new \Exception('No result data returned for bookingDetails.');
        }

        $output = '';

        foreach ($bookingDetails->result as $bookingData) {
            if (! is_object($bookingData)) {
                Log::channel('import')->warning("bookingData isn't an object: " . json_encode($bookingData));

                continue;
            }

            if (! isset($bookingData->hotel_id)) {
                Log::channel('import')->warning('hotel id is empty : ' . json_encode($bookingData));

                continue;
            }

            if ((int) $bookingData->affiliate_id !== BookingDotComService::DIRECT_LINK_AFFILIATE_ID) {
//                Log::channel('import')->info("Booking.com booking from another affiliate_id ({$bookingData->affiliate_id})");
                continue;
            }

            $storedBooking = Booking::where('system', 'BookingDotCom')
                ->where('bookingID', $bookingData->reservation_id)
                ->first();

            $oldStatus = $storedBooking->status;
            $newStatus = $this->getStatus($bookingData->status);

            if (! $storedBooking || $oldStatus === $newStatus) {
                continue;
            }

            $storedBooking->fill([
                'status' => $newStatus,
                'reject_reason' => $this->getRejectedReason($bookingData->status),
                //                'label' => $bookingData->affiliate_label ?? null,
                //                'device' => $this->getDevice($bookingData->user_device),
            ])->save();

            EventLog::log(
                'system',
                'updateStatus',
                'Booking',
                $storedBooking->id,
                $storedBooking->system,
                [
                    'from' => $oldStatus,
                    'to' => $this->getStatus($bookingData->status),
                    'reject_reason' => $this->getRejectedReason($bookingData->status),
                ]
            );
        }

        return $output;
    }

    private function getStatus(string $bookingStatus): StatusBooking
    {
        return match ($bookingStatus) {
            'stayed' => StatusBooking::Stayed,
            'booked' => StatusBooking::Booked,
            default => StatusBooking::Cancelled
        };
    }

    private function getRejectedReason(string $bookingStatus): string
    {
        return match ($bookingStatus) {
            'stayed', 'booked' => '',
            default => $bookingStatus
        };
    }

    private function getDevice(string $userDevice): ?DeviceBooking
    {
        return match ($userDevice) {
            'Computer' => DeviceBooking::Desktop,
            'Mobile' => DeviceBooking::Mobile,
            'Tablet' => DeviceBooking::Tablet,
            'App' => DeviceBooking::App,
            default => null
        };
    }
}
