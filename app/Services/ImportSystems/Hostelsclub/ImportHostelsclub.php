<?php

namespace App\Services\ImportSystems\Hostelsclub;

use App\Events\Import\BatchItemImported;
use App\Events\Import\ImportFinished;
use App\Events\Import\ImportStarted;
use App\Services\ImportSystems\Import;
use Illuminate\Support\Facades\Log;
use Lib\DataTools;

class ImportHostelsclub
{
    public const SYSTEM_NAME = 'Hostelsclub';

    public function handle($isTestRun): bool
    {
        $dbFields = [
            ['dataField' => 'ID', 'dbField' => 'intCode', 'isNumber' => true, 'isRequired' => true],
            ['dataField' => 'NAME', 'dbField' => 'name', 'isNumber' => false, 'isRequired' => true],
            ['dataField' => 'ADDRESS', 'dbField' => 'address1', 'isNumber' => false, 'isRequired' => false],
            ['dataField' => 'CITY', 'dbField' => 'city', 'isNumber' => false, 'isRequired' => true],
            ['dataField' => 'CITY_ID', 'dbField' => 'theirCityCode', 'isNumber' => true, 'isRequired' => true],
            ['dataField' => 'COUNTRY', 'dbField' => 'country', 'isNumber' => false, 'isRequired' => true],
            ['dataField' => 'CATEGORY', 'dbField' => 'propertyType', 'isNumber' => false, 'isRequired' => true,
                // (Google "Property Class Type (PCT) OTA codes" for other codes)
                'conversion' => ['1' => 'Hostel', '2' => 'Hotel', '3' => 'Hotel', '4' => 'Hotel', '5' => 'Guesthouse',
                    '6' => 'Guesthouse', '7' => 'Campsite', '8' => 'Hotel', '9' => 'Hotel', '10' => 'Apartment',
                    '12' => 'Hotel', ], ],
        ];

        $data = file_get_contents('https://www.hostelspoint.com/xml_aff/hostels_en.xml');
        $data = explode('<HOSTEL>', $data);
        if (count($data) < 100) {
            throw new \Exception('Invalid data.');
        } // clearly something is wrong

        $header = $data[0];
        unset($data[0]); // xml header

        $importedDataInitialValues = Import::getInitialValues(self::SYSTEM_NAME);

        ImportStarted::dispatch(self::SYSTEM_NAME, ['totalPages' =>  count($data)]);

        foreach ($data as $key => $hostelData) {
            BatchItemImported::dispatch(self::SYSTEM_NAME, $key);

            $hostel = simplexml_load_string($header . '<HOSTEL>' . $hostelData . (! str_contains($hostelData, '</HOSTELS>') ? '</HOSTELS>' : ''));
            if (! $hostel) {
                Log::channel('importedImport')->warning('Skipping invalid hostel.');

                continue;
            }

            $valuesArrayTemp = DataTools::extractDataFields($dbFields, $hostel->HOSTEL, 'object');
            $valuesArrayTemp['urlLink'] = str_replace('#affiliateID#', HostelsclubService::OUR_LINKING_AFFILIATE_ID, $hostel->HOSTEL->URL);
            if (! $valuesArrayTemp) {
                Log::channel('importedImport')->warning("Empty extractDataFields result for: $hostelData");

                continue;
            }

            Import::insertNewImported(array_merge($importedDataInitialValues, $valuesArrayTemp));

            if ($isTestRun) {
                break;
            }
        }

        ImportFinished::dispatch(self::SYSTEM_NAME);

        return true;
    }
}
