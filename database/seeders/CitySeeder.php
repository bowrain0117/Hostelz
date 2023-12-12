<?php

namespace Database\Seeders;

use App\Models\CityInfo;
use App\Models\CountryInfo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $featuresCities = CityInfo::$featuredCitiesNames;

        collect($featuresCities)
            ->each(function (string $cityName) {
                $country = CountryInfo::factory()->create();
                CityInfo::factory()
                    ->create([
                        'country' => $country->country,
                        'continent' => $country->continent,
                        'city' => $cityName,
                    ]);
            });
    }
}
