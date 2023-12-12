<?php

namespace App\Services\ImportSystems\BookingDotCom;

use App\Events\Import\BatchItemImported;
use App\Events\Import\ImportFinished;
use App\Events\Import\ImportStarted;
use App\Events\Import\ImportUpdate;
use App\Jobs\Import\ImportFromCityBookingDotComJob;
use App\Models\CountryInfo;
use App\Models\Imported;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use App\Services\ImportSystems\Import;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportBookingDotCom
{
    public const SYSTEM_NAME = 'BookingDotCom';

    public function handle($isTest): void
    {
        $countries = $this->fetchCountries($isTest);

        ImportStarted::dispatch(self::SYSTEM_NAME, ['totalPages' => $countries->count()]);

        cache()->tags('imported')->put('countriesBookingDotComImport', $countries);

        $this->importCountry();
    }

    public function importCountry(): void
    {
        throw_unless(
            cache()->tags('imported')->has('countriesBookingDotComImport'),
            'RuntimeException',
            'countriesBookingDotComImport not exist'
        );

        /** @var Collection $countries */
        $countries = cache()->tags('imported')->get('countriesBookingDotComImport');
        if ($countries->isEmpty()) {
            Log::channel('importedImport')->info('importCountry countries is empty');

            ImportFinished::dispatch(self::SYSTEM_NAME);

            cache()->tags('imported')->flush();

            return;
        }

        $country = $countries->shift();
        Log::channel('importedImport')->info("country: {$country->name}, remaining {$countries->count()}");

        cache()
            ->tags('imported')
            ->put('countriesBookingDotComImport', $countries);

        $jobs = $this->getJobsForCountry($country);
        if ($jobs->isEmpty()) {
            $this->importCountry();

            return;
        }

        $batch = Bus
            ::batch($jobs)
            ->then(fn (Batch $batch) => $this->importCountry())
            ->catch(fn (Batch $batch, \Throwable $e) => Log::channel('importedImport')
                ->error('importCountry butch error ' . $e->getMessage())
            )
            ->allowFailures()
            ->name("ImportBookingDotCom Country '{$country->name}'")
            ->onQueue('import-sync')
            ->dispatch();

        cache()
            ->tags('imported')
            ->put('bdcImportBatchItem', (object) ['count' => $countries->count(), 'batchId' => $batch->id]);

        BatchItemImported::dispatch(self::SYSTEM_NAME, $countries->count());
    }

    public function fetchCountries(bool $isTest): Collection
    {
        $countries = APIBookingDotCom::doRequest(false, 'countries', [], 60, 10);
        if (! isset($countries->result)) {
            Log::channel('importedImport')->error('ImportBookingDotCom no countries');

            return collect();
        }

        return collect($countries->result)
            ->map(function ($item) {
                $code = $item->country;
                $name = CountryInfo::determineCountryNameFromCountryCode($code);
                if ($name === '') {
                    Log::channel('importedImport')->warning("Can't find country for country code '{$code}'.");

                    return [];
                }

                return (object) compact('name', 'code');
            })
            ->filter()
            ->when($isTest, fn ($items) => $items->slice(0, 6));
    }

    public function getJobsForCountry($country): Collection
    {
        $offset = 0;
        $rowsPerRequest = 1000;
        $batches = collect();

        do {
            $cities = $this->fetchCities($country->code, $rowsPerRequest, $offset);

            $cities->reduce(function ($batches, $city) use ($country): Collection {
                if ((int) $city->nr_hotels === 0) {
                    return $batches;
                }

                $batches->push(new ImportFromCityBookingDotComJob($city, $country->name));

                return $batches;
            }, $batches);

            $offset += $rowsPerRequest;
        } while (! $cities->isEmpty());

        return $batches;
    }

    public function importFromCity(object $city, string $countryName): void
    {
        $importedDataInitialValues = Import::getInitialValues(self::SYSTEM_NAME);

        ImportUpdate::dispatch();

        $this->fetchHostelsData($city->city_id)
             ->each(function ($row) use ($countryName, $importedDataInitialValues) {
                 if ($row->hotel_data->is_closed) {
                     Log::channel('importedImport')->warning('closed, name: ' . $row->hotel_data->name);

                     return true;
                 }

                 $values = $this->getValues($row, $countryName);

                 Import::insertNewImported(array_merge($importedDataInitialValues, $values));
             });
    }

    private function fetchHostelsData($cityId): Collection
    {
        try {
            $data = APIBookingDotCom::doRequest(false, 'hotels', [
                'city_ids' => $cityId,
                'extras' => 'hotel_info,hotel_photos,hotel_facilities,room_info,room_description',
                'hotel_type_ids' => 203,
            ], 60, 10);
        } catch (\Throwable $e) {
            Log::channel('importedImport')
               ->error("fetchHostelsData for cityId:{$cityId}; message: '{$e->getMessage()}' exception:" . json_encode($e));

            return collect();
        }

        return collect($data->result);
    }

    public function fetchCities($countryCode, int $rowsPerRequest, int $offset): Collection
    {
        try {
            $cities = APIBookingDotCom::doRequest(false, 'cities', [
                'countries' => $countryCode,
                'languages' => 'en',
                'rows' => $rowsPerRequest,
                'offset' => $offset,
            ], 60, 10);
        } catch (\Throwable $throwable) {
            Log::channel('importedImport')->warning('No cities for countryCode: ' . $countryCode . ' error: ' . json_encode($throwable));

            return collect();
        }

        return collect($cities->result);
    }

    public function getValues(object $row, string $country): array
    {
        $propertyTypes = [
            201 => 'Apartment', 203 => 'Hostel', 208 => 'Guesthouse', 214 => 'Campsite', 216 => 'Guesthouse',
        ];

        $hotelData = $row->hotel_data;

        return [
            'address1' => $hotelData->address,
            'arrivalEarliest' => (int) $hotelData->checkin_checkout_times->checkin_from,
            'arrivalLatest' => (int) $hotelData->checkin_checkout_times->checkout_to,
            'city' => $hotelData->city,
            'country' => $country,
            'features' => $this->getFeaturesFromFacilities($hotelData->hotel_facilities, $row->room_data),
            'intCode' => $row->hotel_id,
            'latitude' => $hotelData->location->latitude,
            'longitude' => $hotelData->location->longitude,
            'localCurrency' => $hotelData->currency,
            'maxPeople' => $hotelData->max_persons_in_reservation,
            'name' => $hotelData->name,
            'pics' => $this->getPics($hotelData),
            'propertyType' => data_get($propertyTypes, $hotelData->hotel_type_id, 'Hotel'),
            'theirCityCode' => $hotelData->city_id,
            'urlLink' => $hotelData->url,
            'zipcode' => $hotelData->zip,
        ];
    }

    public function getPics($hotelData): array
    {
        if (empty($hotelData->hotel_photos)) {
            return [];
        }

        return collect($hotelData->hotel_photos)
            ->map(fn ($item) => $item->url_original ?? null)
            ->filter()
            ->unique()
            ->slice(0, Imported::MAX_PICS_PER_IMPORTED)
            ->map(fn ($item) => preg_replace('|hotel\/max\d+\/|im', 'hotel/max' . Listing::BIG_PIC_MAX_WIDTH . '/', $item))
            ->toArray();
    }

    public function getFeaturesFromFacilities(array $facilities, array $roomData = []): array
    {
        $featureMap = $this->getFeatureMap();

        $raw_facilities = collect($facilities)
            ->filter(fn ($item) => ! isset($item->attrs) || ! in_array('paid', $item->attrs))
            ->map(fn ($item) => $item->name)
            ->when($this->hasFemaleDorm($roomData), fn (Collection $collection) => $collection->push('Female-Only Dorms'))
            //  use only in $featureMap
            ->intersect(array_keys($featureMap))
            ->toArray();

        return ListingFeatures::mapFromImportedFeatures($raw_facilities, $featureMap);
    }

    public function hasFemaleDorm($roomData): bool
    {
        $phrases = AvailabilityBookingDotCom::getPhrases();

        return collect($roomData)
            ->filter(fn ($roomTypeData) => str($roomTypeData->room_name)->contains($phrases['dorm'], true)
                && str($roomTypeData->room_name)->contains($phrases['female'], true)
            )
            ->isNotEmpty();
    }

    private function getFeatureMap(): array
    {
        return [
            '24-hour front desk' => [
                'reception' => '24hours',
            ],
            '24-hour security' => [
                'extras' => '24HourSecurity',
            ],
            'Adult only' => [
                'goodFor' => 'partying',
            ],
            'Air conditioning' => [
                'extras' => 'ac',
            ],
            'Airport shuttle' => null,
            'Airport shuttle (additional charge)' => null,
            'Allergy-free room' => null,
            'BBQ facilities' => [
                'extras' => 'bbq',
            ],
            'Baby safety gates' => [
                'goodFor' => 'families',
            ],
            'Bar' => [
                'extras' => 'bar',
                'goodFor' => 'partying',
            ],
            'Barber/beauty shop' => null,
            'Beach' => [
                'extras' => 'beach',
                'goodFor' => 'beach_hostels',
            ],
            'Beachfront' => [
                'extras' => 'beach',
                'goodFor' => 'beach_hostels',
            ],
            'Bicycle rental (additional charge)' => null,
            'Bike tours' => [
                'extras' => 'bike_tours',
                'goodFor' => 'socializing',
            ],
            'Billiards' => [
                'extras' => 'pooltable',
                'goodFor' => 'youth_hostels',
            ],
            'Board games/puzzles' => [
                'goodFor' => 'youth_hostels',
            ],
            'Bowling' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Café' => [
                'extras' => 'food',
                //                'goodFor' => 'business', todo: Check!
            ],
            'Canoeing' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Children\'s playground' => [
                'goodFor' => 'families',
            ],
            'Coffee house on site' => '',
            'Concierge service' => null,
            'Currency exchange' => [
                'extras' => 'exchange',
            ],
            'Cycling' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Daily housekeeping' => null,
            'Darts' => [
                'extras' => 'darts',
                'goodFor' => 'socializing',
            ],
            'Designated smoking area' => null,
            'Diving' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Evening entertainment' => [
                'extras' => 'evening_entertainment',
                'goodFor' => 'socializing',
            ],
            'Express check-in/check-out' => null,
            'Family rooms' => [
                'goodFor' => 'families',
            ],
            'Female-Only Dorms' => [
                'roomTypes' => 'female_only_dorms',
            ],
            'Fishing' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Fitness' => [
                'extras' => 'gym',
                'goodFor' => 'business',
            ],
            'Fitness Centre' => [
                'extras' => 'gym',
                'goodFor' => 'business',
            ],
            'Fitness classes' => [
                'extras' => 'gym',
                'goodFor' => 'business',
            ],
            'Free WiFi' => [
                'wifiCommons' => 'free',
            ],
            'Games room' => [
                'extras' => 'gameroom',
                'goodFor' => 'youth_hostels',
            ],
            'Gift shop' => null,
            'Golf course (within 3 km)' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Heating' => null,
            'Hiking' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Horse riding' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Indoor pool' => [
                'extras' => 'swimming',
            ],
            'Indoor pool (all year)' => [
                'extras' => 'swimming',
            ],
            'Indoor pool (seasonal)' => [
                'extras' => 'swimming',
            ],
            'Internet services' => null,
            'Ironing service' => null,
            'Karaoke' => [
                'extras' => 'karaoke',
                'goodFor' => 'socializing',
            ],
            'Kid meals' => [
                'goodFor' => 'families',
            ],
            'Kid-friendly buffet' => [
                'goodFor' => 'families',
            ],
            'Kids\' club' => [
                'goodFor' => 'families',
            ],
            'Kids\' outdoor play equipment' => [
                'goodFor' => 'families',
            ],
            'Kids\' pool' => [
                'goodFor' => 'families',
            ],
            'Library' => null,
            'Lift' => null,
            'Live music/performance' => [
                'extras' => 'live_music_performance',
                'goodFor' => 'socializing',
            ],
            'Lockers' => null,
            'Luggage storage' => null,
            'Meeting/banquet facilities' => [
                'extras' => 'meeting_banquet_facilities',
                'goodFor' => 'business',
            ],
            'Mini golf' => [
                'goodFor' => 'youth_hostels',
            ],
            'Mini-market on site' => null,
            'Nightclub/DJ' => [
                'extras' => 'nightclub',
            ],
            'Non-smoking rooms' => null,
            'Non-smoking throughout' => null,
            'Outdoor pool' => [
                'extras' => 'swimming',
            ],
            'Outdoor pool (all year)' => [
                'extras' => 'swimming',
            ],
            'Outdoor pool (seasonal)' => [
                'extras' => 'swimming',
            ],
            'Packed lunches' => null,
            'Parking' => 'parking',
            'Parking garage' => 'parking',
            'Pets allowed' => [
                'petsAllowed' => 'yes',
            ],
            'Private beach area' => [
                'extras' => 'beach',
                'goodFor' => 'beach_hostels',
            ],
            'Private check-in/check-out' => null,
            'Pub crawls' => [
                'goodFor' => 'partying',
            ],
            'Restaurant' => [
                'extras' => 'food',
            ],
            'Safety deposit box' => null,
            'Secured parking' => null,
            'Shared kitchen' => 'kitchen',
            'Shared lounge/TV area' => [
                'extras' => 'movies',
            ],
            'Shops (on site)' => null,
            'Shuttle service (additional charge)' => null,
            'Ski equipment hire on site' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Ski school' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Skiing' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Snack bar' => null,
            'Soundproof rooms' => null,
            'Special diet menus (on request)' => null,
            'Street parking' => null,
            'Sun terrace' => null,
            'Swimming Pool' => [
                'extras' => 'swimming',
            ],
            'Swimming pool' => [
                'extras' => 'swimming',
            ],
            'Table tennis' => [
                'extras' => 'table_tennis',
                'goodFor' => [
                    'socializing',
                    'youth_hostels',
                ],
            ],
            'Tennis courts' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Terrace' => null,
            'Themed dinner nights' => [
                'extras' => 'themed_dinner_nights',
                'goodFor' => 'socializing',
            ],
            'Ticket service' => null,
            'Tour desk' => 'tours',
            'Tour or class about local culture' => [
                'extras' => 'tour_class_local_culture',
                'goodFor' => 'socializing',
            ],
            'Vending machine (drinks)' => null,
            'Vending machine (snacks)' => null,
            'Walking tours' => [
                'extras' => 'walking_tours',
                'goodFor' => 'socializing',
            ],
            'Wheelchair accessible' => 'wheelchair',
            'WiFi' => null,
            'Windsurfing' => [
                'extras' => 'sport',
                'goodFor' => 'adventure_hostels',
            ],
            'Yoga classes' => [
                'extras' => 'yoga_classes',
                'goodFor' => 'female_solo_traveller',
            ],
        ];
    }
}
