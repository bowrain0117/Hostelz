<?php

namespace App\Services\ImportSystems\BookHostels;

use App\Events\Import\BatchItemImported;
use App\Events\Import\ImportFinished;
use App\Events\Import\ImportStarted;
use App\Jobs\Import\ImportFromPageBookHostelsJob;
use App\Models\Imported;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use App\Services\ImportSystems\Import;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Lib\DataTools;

class ImportBookHostels
{
    public const SYSTEM_NAME = 'BookHostels';

    public function handle(bool $isTestRun): void
    {
        $pageCount = $this->getPagesCount($isTestRun);

        $jobs = collect()
            ->range(1, $pageCount)
            ->map(fn ($page) => new ImportFromPageBookHostelsJob($page));

        $batch = Bus
            ::batch($jobs)
            ->then(function (Batch $batch) {
                ImportFinished::dispatch(self::SYSTEM_NAME, ['batchId' => $batch->id]);

                cache()->tags('imported')->flush();
            })
            ->catch(fn (Batch $batch, \Throwable $e) => Log::channel('importedImport')
                ->error('ImportBookHostels butch error ' . $e->getMessage())
            )
            ->allowFailures()
            ->name('ImportBookHostels')
            ->onQueue('import-sync')
            ->dispatch();

        ImportStarted::dispatch(self::SYSTEM_NAME, ['batchId' => $batch->id, 'totalPages' => $pageCount]);
    }

    private function getPagesCount(bool $isTestRun): int
    {
        if ($isTestRun) {
            return 2;
        }

        $result = APIBookHostels::doRequest('propertiesinformation', [], 90, 4);
        if (empty($result['data']['PagesCount'])) {
            Log::channel('importedImport')
                ->warning('getPagesCount no pages');

            return 0;
        }

        return (int) $result['data']['PagesCount'];
    }

    public static function importPage(int $page): void
    {
        Log::channel('importedImport')->info("- importPage for page {$page}");

        $result = APIBookHostels::doRequest('propertiesinformation', ['PageNumber' => $page], 90, 4);
        if (! isset($result['data']['Properties'])) {
            Log::channel('importedImport')->warning("propertiesinformation failed on page {$page}; result: " . json_encode($result));

            return;
        }

        $importedDataInitialValues = Import::getInitialValues(self::SYSTEM_NAME);

        foreach ($result['data']['Properties'] as $property) {
            if (! $property) {
                continue;
            }

            $values = self::getValues($property);

            if (! $values) {
                continue;
            }

            Import::insertNewImported(array_merge($importedDataInitialValues, $values));
        }

        BatchItemImported::dispatch(self::SYSTEM_NAME, $page);
    }

    public static function getValues(array $property): array
    {
        $dbFields = self::getDbField();

        $values = DataTools::extractDataFields($dbFields, $property, 'array');
        if (! $values) {
            Log::channel('import')->warning('Empty extractDataFields result for ' . json_encode($property));

            return [];
        }

        $values['urlLink'] = self::getHostelworldURL($values['city'], $values['name'], $values['intCode']);

        $values['pics'] = self::getPicsUrls(data_get($property, 'propertyImages', []));

        $values['features'] = self::getFeaturesFromFacilities(data_get($property, 'facilities', []));

        if (isset($property['policies'])) {
            $policyFeatureMap = [
                'Age Restriction' => null,
                'Cash Only' => ['cc' => 'no'],
                'Child Friendly' => ['goodFor' => 'families'],
                'Credit Cards Accepted' => 'cc',
                'Credit Cards Not Accepted' => ['cc' => 'no'],
                'Curfew' => null, // (we would need to know the time)
                'Lockout' => 'lockout',
                'No Curfew' => ['curfew' => 'noCurfew'],
                'Non Smoking' => 'allNonsmoking',
                'Pet Friendly' => ['petsAllowed' => 'yes'],
                'Taxes Included' => null,
                'Taxes Not Included' => null,
            ];

            $policyFeatures = ListingFeatures::mapFromImportedFeatures($property['policies'], $policyFeatureMap);
            if ($policyFeatures) {
                $values['features'] = (! empty($values['features']) ? ListingFeatures::merge($values['features'], $policyFeatures) : $policyFeatures);
            }
        }

        return $values;
    }

    public static function getFeaturesFromFacilities(array $facilities): array
    {
        $featureMap = self::getFeatureMap();

        return ListingFeatures::mapFromImportedFeatures($facilities, $featureMap);
    }

    public static function getDbField(): array
    {
        return [
            ['dataField' => 'propertyNumber', 'dbField' => 'intCode', 'isNumber' => true, 'isRequired' => true],
            ['dataField' => 'propertyName', 'dbField' => 'name', 'isNumber' => false, 'isRequired' => true],
            ['dataField' => 'address1', 'dbField' => 'address1', 'isNumber' => false, 'isRequired' => false],
            ['dataField' => 'address2', 'dbField' => 'address2', 'isNumber' => false, 'isRequired' => false],
            ['dataField' => 'geo', 'nested' => [
                ['dataField' => 'latitude', 'dbField' => 'latitude', 'isNumber' => true, 'isRequired' => false],
                ['dataField' => 'longitude', 'dbField' => 'longitude', 'isNumber' => true, 'isRequired' => false],
            ]],
            ['dataField' => 'city', 'dbField' => 'city', 'isNumber' => false, 'isRequired' => true],
            ['dataField' => 'province', 'dbField' => 'region', 'isNumber' => false, 'isRequired' => false], // exists?
            ['dataField' => 'CityNO', 'dbField' => 'theirCityCode', 'isNumber' => true, 'isRequired' => false],
            ['dataField' => 'country', 'dbField' => 'country', 'isNumber' => false, 'isRequired' => true],
            ['dataField' => 'propertyType', 'dbField' => 'propertyType', 'isNumber' => false, 'isRequired' => true,
                // (keys are converted to all lowercase)
                'conversion' => ['hostel' => 'Hostel', 'hotel' => 'Hotel', 'apartment' => 'Apartment',
                    'campsite' => 'Campsite', 'guesthouse' => 'Guesthouse', ], ],
            ['dataField' => 'currency', 'dbField' => 'localCurrency', 'isNumber' => false, 'isRequired' => false],
            // Note: Their docs say email was removed in version 1.2 of their API.
            ['dataField' => 'email', 'dbField' => 'email', 'isNumber' => false, 'isRequired' => false], // I think this is no longer actually in their data
            ['dataField' => 'tel', 'dbField' => 'tel', 'isNumber' => false, 'isRequired' => false], // I think this is no longer actually in their data
            ['dataField' => 'maxPax', 'dbField' => 'maxPeople', 'isNumber' => false, 'isRequired' => false],
            // (disabled because new site format is different) [ 'dataField' => 'facilities', 'dbField' => 'features', 'isNumber' => false, 'isRequired' => false, 'commaSeparateMultiples' => true ],
            ['dataField' => 'checkInTime', 'nested' => [
                ['dataField' => 'earliest', 'dbField' => 'arrivalEarliest', 'isNumber' => true, 'isRequired' => false],
                ['dataField' => 'latest', 'dbField' => 'arrivalLatest', 'isNumber' => true, 'isRequired' => false],
            ]],
            ['dataField' => 'prices', 'nested' => [
                ['dataField' => 'SharedMinPrice', 'nested' => [
                    ['dataField' => 'EUR', 'dbField' => 'sharedPrice', 'isNumber' => true, 'emptyOK' => true],
                ]],
                ['dataField' => 'PrivateMinPrice', 'nested' => [
                    ['dataField' => 'EUR', 'dbField' => 'privatePrice', 'isNumber' => true, 'emptyOK' => true],
                ]],
            ]],
        ];
    }

    public static function getFeatureMap(): array
    {
        return [
            '24 Hour Reception' => [
                'reception' => '24hours',
            ],
            '24 Hour Security' => [
                'extras' => '24HourSecurity',
            ],
            'ATM' => [
                'extras' => 'atm',
            ],
            'Adaptors' => null,
            'Air Conditioning' => [
                'extras' => 'ac',
            ],
            'Airport Transfers' => 'airportPickup',
            'BBQ' => [
                'extras' => 'bbq',
            ],
            'Bar' => [
                'extras' => 'bar',
            ],
            'Beauty Salon' => null,
            'Bicycle Hire' => 'bikeRental',
            'Bicycle Parking' => null,
            'Board games' => [
                'extras' => 'board_games',
                'goodFor' => 'youth_hostels',
            ],
            'Book Exchange' => null,
            'Breakfast Not Included' => null,
            'Business centre' => [
                'goodFor' => 'business',
            ],
            'Cable TV' => [
                'extras' => 'cableTV',
            ],
            'Cafe' => [
                'extras' => 'food',
            ],
            'Café' => [
                'extras' => 'food',
            ],
            'Card Phones' => null,
            'Ceiling Fan' => null,
            'Children\'s play area' => [
                'goodFor' => 'families',
            ],
            'Common Room' => 'lounge',
            'Concierge' => null,
            'Cooker' => null,
            'Cots available' => [
                'goodFor' => 'families',
            ],
            'Currency Exchange' => [
                'extras' => 'exchange',
            ],
            'DRYCLEANING' => null,
            'DVD\'s' => [
                'extras' => 'movies',
            ],
            'Direct Dial Telephone' => null,
            'Dishwasher' => null,
            'Dryer' => [
                'extras' => 'dryer',
            ],
            'Elevator' => [
                'extras' => 'elevator',
            ],
            'Express check-in / out' => null,
            'Fax Service' => null,
            'Fitness Centre' => [
                'extras' => 'gym',
                'goodFor' => 'business',
            ],
            'Flexible NRR' => null,
            'Follows Covid-19 sanitation guidance' => null,
            'Foosball' => [
                'extras' => 'gameroom',
                'goodFor' => 'youth_hostels',
            ],
            'Free Airport Transfer' => [
                'airportPickup' => 'free',
            ],
            'Free Breakfast' => [
                'breakfast' => 'free',
            ],
            'Free City Maps' => null,
            'Free City Tour' => null,
            'Free Internet Access' => null,
            'Free Parking' => [
                'parking' => 'free',
            ],
            'Free WiFi' => [
                'wifiCommons' => 'free',
            ],
            'Fridge/Freezer' => null,
            'Games Room' => [
                'extras' => 'gameroom',
                'goodFor' => 'youth_hostels',
            ],
            'Golf Course' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Hair Dryers' => [
                'extras' => 'hairdryers',
            ],
            'Hair Dryers For Hire' => [
                'extras' => 'hairdryers',
            ],
            'Hot Showers' => [
                'extras' => 'hotShowers',
            ],
            'Hot Tub' => [
                'extras' => 'hottub',
            ],
            'Housekeeping' => null,
            'Indoor Swimming Pool' => [
                'extras' => 'swimming',
            ],
            'Internet Access' => null,
            'Internet café' => null,
            'Iron/Ironing Board' => null,
            'Jobs Board' => null,
            'Key Card Access' => null,
            'Kitchen' => 'kitchen',
            'Late check-out' => null,
            'Laundry Facilities' => [
                'extras' => 'laundry',
            ],
            'Linen Included' => [
                'sheets' => 'free',
            ],
            'Linen Not Included' => [
                'sheets' => 'pay',
            ],
            'Lockers' => 'lockersInCommons',
            'Lounge' => 'lounge',
            'Luggage Storage' => 'luggageStorage',
            'Meals available' => [
                'extras' => 'food',
            ],
            'Meeting Room' => [
                'extras' => 'meeting_banquet_facilities',
                'goodFor' => 'business',
            ],
            'Microwave' => null,
            'Mini-Supermarket' => null,
            'Minibar' => null,
            'Nightclub' => [
                'extras' => 'nightclub',
            ],
            'Outdoor Swimming Pool' => [
                'extras' => 'swimming',
            ],
            'Outdoor Terrace' => null,
            'Parking' => 'parking',
            'PlayStation' => [
                'extras' => 'videoGames',
            ],
            'Pool Table' => [
                'extras' => 'pooltable',
                'goodFor' => 'youth_hostels',
            ],
            'Postal Service' => null,
            'Reading Light' => null,
            'Reception (limited hours)' => null,
            'Restaurant' => [
                'extras' => 'food',
            ],
            'Room Service  (24 hours)' => null,
            'Room Service (24 hours)' => null,
            'Room service (limited hours)' => null,
            'STEAMROOM' => null,
            'Safe Deposit Box' => 'safeDepositBox',
            'Sauna' => null,
            'Security Lockers' => 'lockersInCommons',
            'Self-Catering Facilities' => null,
            'Servizi aggiornate' => null,
            'Shuttle Bus' => null,
            'Steam room' => null,
            'Swimming Pool' => [
                'extras' => 'swimming',
            ],
            'Tea &amp; Coffee Making Facilities' => null,
            'Telephone/Fax Facilities' => null,
            'Tennis courts' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Tours/Travel Desk' => 'tours',
            'Towels Included' => [
                'towels' => 'free',
            ],
            'Towels Not Included' => [
                'towels' => 'no',
            ],
            'Towels for hire' => [
                'towels' => 'pay',
            ],
            'Utensils' => null,
            'Vending Machines' => null,
            'Wake-up calls' => null,
            'Washing machine' => [
                'extras' => 'laundry',
            ],
            'Wheelchair Friendly' => 'wheelchair',
            'Wi-Fi' => null,
            'Wii' => [
                'extras' => 'videoGames',
            ],
        ];
    }

    public static function getHostelworldURL($city, $name, $intCode): string
    {
        // Note that this isn't an affiliate link.  We add the affiliate encoding as needed for special pages like cheapHostels.php with tracking codes also added.
        return 'http://www.hostelworld.com/hosteldetails.php/' . urlencode(replaceNonUrlCharWithDash($city)) . '/' . urlencode(replaceNonUrlCharWithDash($name)) . '/' . $intCode;
    }

    public static function getPicsUrls(array $propertyImages): array
    {
        if (count($propertyImages) === 0) {
            return [];
        }

        return collect($propertyImages)
            ->filter(fn ($item) => $item['imageSize'] === 'max')
            ->pluck('imageURL')
            ->unique()
            ->slice(0, Imported::MAX_PICS_PER_IMPORTED)
            ->map(fn ($item) => str_replace('f_auto,q_auto', urlencode('f_auto,q_auto,w_' . Listing::BIG_PIC_MAX_WIDTH), $item))
            ->toArray();
    }
}
