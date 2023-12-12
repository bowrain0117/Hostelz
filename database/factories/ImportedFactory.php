<?php

namespace Database\Factories;

use App\Models\Imported;
use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use App\Services\ImportSystems\BookingDotCom\ImportBookingDotCom;
use App\Services\ImportSystems\Hostelsclub\ImportHostelsclub;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class ImportedFactory extends Factory
{
    public function definition()
    {
        return [
            'address1' => fake()->address,
            'arrivalEarliest' => 11,
            'arrivalLatest' => 23,
            'city' => fake()->city,
            'country' => fake()->country,
            'features' => [],
            'intCode' => fake()->numberBetween(),
            'latitude' => fake()->latitude,
            'localCurrency' => 'eur',
            'longitude' => fake()->longitude,
            'maxPeople' => 5,
            'name' => fake()->words(3, true),
            'pics' => $this->getPicsUrls(),
            'propertyType' => 'Hostel',
            'status' => Imported::STATUS_ACTIVE,
            'system' => $this->faker->randomElement([ImportBookHostels::SYSTEM_NAME, ImportBookingDotCom::SYSTEM_NAME, ImportHostelsclub::SYSTEM_NAME]),
            'theirCityCode' => fake()->numberBetween(),
            'urlLink' => fake()->url,
            'zipcode' => fake()->postcode,
        ];
    }

    public function addPicsUrls(int $count = 12)
    {
        return $this->state(['pics' => $this->getPicsUrls($count)]);
    }

    public function getPicsUrlsCollection(int $count = 12): Collection
    {
        return str($this->getPicsUrls($count))->explode(',');
    }

    public function getPicsUrls(int $count = 12): string
    {
        $date = now()->toTimeString();
        $name = fake()->word();

        return collect()
            ->range(0, $count - 1)
            ->map(fn ($item, $key) => "https://fakeimg.pl/400x200/?text={$name}-{$key}-{$date}")
            ->join(',');
    }
}
