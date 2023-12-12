<?php

namespace App\Services\ImportSystems\BookHostels;

use App\Enums\DeviceBooking;
use App\Enums\StatusBooking;
use App\Helpers\EventLog;
use App\Models\Booking;
use App\Models\Imported;
use App\Models\Languages;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Lib\Currencies;

class MaintenanceBookHostels
{
    public function addNewBookings(): string
    {
        set_time_limit(60 * 60);

        $output = '';

        $offset = 0;

        do {
            $date = Carbon::now()->setTimezone('GMT'); // guessing that's their timezone maybe.
            $option = [
                'start_date' => with(clone $date)->subDays(10)->format('Y-m-d'),
                'offset' => $offset,
            ];
            $data = APIBookHostels::getConversionReport($option);

            if (! $data) {
                Log::channel('import')->warning('No result data returned for bookings report for option ' . json_encode($option));

                return $output;
            }

            if (! $data->conversions) {
                Log::channel('import')->warning('No conversions in booking report.'); // normal if no bookings?

                return $output;
            }

            foreach ($data->conversions as $bookingData) {
                $bookingData = $bookingData->conversion_data;
                $conversionItem = $bookingData->conversion_items[0];

                $theirHostelID = (int) $conversionItem->sku;
                if (! $theirHostelID) {
                    // (This can happen with bookings with a "rejected" status)
                    Log::channel('import')->warning('No sku for BookHostels booking: ' . json_encode($bookingData));

                    continue;
                }

                $bookingID = $theirHostelID . '-' . $bookingData->conversion_reference;

                if (Booking::where('system', 'BookHostels')->where('bookingID', $bookingID)->exists()) {
//                    Log::channel('import')->warning("BookHostels booking exists for $theirHostelID.");
                    continue;
                }

                $imported = Imported::where('system', 'BookHostels')->where('intCode', $theirHostelID)->first();

                if (! $imported) {
                    Log::channel('import')->warning("BookHostels booking for unknown intCode $theirHostelID.");

                    continue;
                }

                $commissionAmount = Currencies::convert($bookingData->conversion_value->publisher_commission, 'EUR', 'USD');
                if (! isset($bookingData->meta_data)) {
                    Log::channel('import')->warning("No meta data for booking $bookingID.");

                    continue;
                }

                if (! isset($bookingData->meta_data->deposit_eur)) {
                    // (This seems to happen with some of them.)
                    Log::channel('import')->warning("No deposit_eur for booking $bookingID.");

                    continue;
                }

                $depositAmount = Currencies::convert($bookingData->meta_data->deposit_eur, 'EUR', 'USD');

                $startDate = (! empty($conversionItem->meta_data->check_in_date)) ?
                    Carbon::createFromFormat('Ymd', $conversionItem->meta_data->check_in_date)->format('Y-m-d')
                    : '0000-00-00';

                $endDate = (! empty($conversionItem->meta_data->check_out_date)) ?
                    Carbon::createFromFormat('Ymd', $conversionItem->meta_data->check_out_date)->format('Y-m-d')
                    : '0000-00-00';

                $language = Languages::isKnownLanguageCode($conversionItem->meta_data->site_language) ?
                    $conversionItem->meta_data->site_language : '';

                $booking = new Booking([
                    'system' => 'BookHostels',
                    'status' => $this->getStatus($conversionItem->item_status),
                    'reject_reason' => $conversionItem->reject_reason ?? null,
                    'bookingID' => $bookingID,
                    'importedID' => $imported->id,
                    'bookingTime' => Carbon::createFromFormat('Y-m-d H:i:s', $bookingData->conversion_time, 'Europe/Paris')
                        ->setTimezone(date_default_timezone_get()),
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'nights' => $conversionItem->meta_data->number_of_nights,
                    'people' => $conversionItem->meta_data->total_guests,
                    'depositUSD' => $depositAmount,
                    'commission' => $commissionAmount,
                    'language' => $language,
                    'nationality' => $conversionItem->meta_data->nationality ?? '',
                    'label' => $bookingData->publisher_reference ?? null,
                    'device' => $this->getDevice($bookingData->ref_device),
                ]);

                $trackingCode = str($bookingData->publisher_reference)->after('_t_');

                if ($booking->validateAndSave($trackingCode)) {
                    $output .= "Saved booking $booking->id. ";
                }
            }

            $offset += $data->limit;
        } while ($offset < $data->count);

        return $output;
    }

    public function updateStatus(): string
    {
        set_time_limit(60 * 60);

        $output = '';

        $offset = 0;

        do {
            $date = Carbon::now()->setTimezone('GMT'); // guessing that's their timezone maybe.
            $option = [
                'start_date' => with(clone $date)->subDays(2)->format('Y-m-d'),
                'offset' => $offset,
                'statuses' => ['rejected', 'approved'],
                'date_type' => 'last_modified',
            ];
            $data = APIBookHostels::getConversionReport($option);

            if (! $data) {
                Log::channel('import')->warning('No result data returned for bookings report for option ' . json_encode($option));

                return $output;
            }

            if (! $data->conversions) {
                Log::channel('import')->warning('No conversions in booking report.'); // normal if no bookings?

                return $output;
            }

            foreach ($data->conversions as $bookingData) {
                $bookingData = $bookingData->conversion_data;
                $conversionItem = $bookingData->conversion_items[0];

                $theirHostelID = (int) $conversionItem->sku;
                if (! $theirHostelID) {
                    // (This can happen with bookings with a "rejected" status)
                    Log::channel('import')->warning('No sku for BookHostels booking: ' . json_encode($bookingData));

                    continue;
                }

                $bookingID = $theirHostelID . '-' . $bookingData->conversion_reference;

                $storedBooking = Booking::where('system', 'BookHostels')->where('bookingID', $bookingID)->first();

                $oldStatus = $storedBooking->status;
                $newStatus = $this->getStatus($conversionItem->item_status);

                if (! $storedBooking || $oldStatus === $newStatus) {
                    continue;
                }

                $storedBooking->fill([
                    'status' => $newStatus,
                    'reject_reason' => $conversionItem->reject_reason ?? null,
                    //                    'label' => $bookingData->publisher_reference ?? null,
                    //                    'device' => $this->getDevice($bookingData->ref_device),
                ])->save();

                EventLog::log(
                    'system',
                    'updateStatus',
                    'Booking',
                    $storedBooking->id,
                    $storedBooking->system,
                    [
                        'from' => $oldStatus,
                        'to' => $newStatus,
                        'reject_reason' => $conversionItem->reject_reason ?? null,
                    ]
                );
            }

            $offset += $data->limit;
        } while ($offset < $data->count);

        return $output;
    }

    private function getStatus(string $bookingStatus): StatusBooking
    {
        return match ($bookingStatus) {
            'approved' => StatusBooking::Stayed,
            'pending' => StatusBooking::Booked,
            'rejected' => StatusBooking::Cancelled,
        };
    }

    private function getDevice(string $userDevice): ?DeviceBooking
    {
        return match ($userDevice) {
            'Desktop' => DeviceBooking::Desktop,
            'Mobile' => DeviceBooking::Mobile,
            'Tablet' => DeviceBooking::Tablet,
            'App' => DeviceBooking::App,
            default => null
        };
    }
}
