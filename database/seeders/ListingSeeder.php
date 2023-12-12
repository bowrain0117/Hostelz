<?php

namespace Database\Seeders;

use App\Enums\CategorySlp;
use App\Models\AttachedText;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Imported;
use App\Models\Listing\Listing;
use App\Models\PriceHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CityInfo::each($this->addListingsForCity(...));
    }

    protected function addListingsForCity(CityInfo $city)
    {
        Listing::factory([
            'city' => $city->city,
            'continent' => $city->continent,
            'country' => $city->country,
            'region' => $city->region,
            'propertyType' => 'Hostel',
        ])
            ->has(
                PriceHistory::factory([
                    'month' => fake()->dateTimeBetween('-3 month', '3 month')->modify('first day of this month'),
                    'roomType' => 'dorm',
                    'averagePricePerNight' => '20',
                ])
                    ->count(3)
            )
            ->has(
                Imported::factory()->count(2)
            )
            ->count(5)
            ->create();
    }
}
