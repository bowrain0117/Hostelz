<?php

namespace App\Services\ImportSystems\BookingDotCom;

use App\Models\Imported;
use App\Services\ImportSystems\Import;
use Illuminate\Support\Facades\Log;

class UpdateImportedBookingDotCom
{
    public function handle(Imported $imported): void
    {
        $importData = $this->getImportData($imported->intCode);

        $values = (new ImportBookingDotCom)->getValues($importData, $imported->country);

        Import::update($imported, $values);
    }

    private function getImportData(int $intCode): object
    {
        try {
            $data = APIBookingDotCom::doRequest(false, 'hotels', [
                'hotel_ids' => $intCode,
                'extras' => 'hotel_info,hotel_photos,hotel_facilities,room_info,room_description',
            ], 60, 10);
        } catch (\Throwable $e) {
            Log::channel('importedImport')
                ->error("hotel_id:{$intCode}; message: '{$e->getMessage()}' exception:" . json_encode($e));

            $data = null;
        }

        return $data?->result[0] ?? (object) [];
    }
}
