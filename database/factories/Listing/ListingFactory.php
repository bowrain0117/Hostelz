<?php

namespace Database\Factories\Listing;

use App\Models\CityInfo;
use App\Models\Listing\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'propertyType' => $this->faker->randomElement(['Hostel', 'Apartment']),
            'geocodingLocked' => $this->faker->boolean(20),
            'ownerLatitude' => $this->faker->latitude(),
            'ownerLongitude' => $this->faker->longitude(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'name' => $this->faker->words(random_int(2, 5), true),
            'city' => $this->faker->city(),
            'continent' => $this->faker->city(),
            'country' => $this->faker->country(),
            'region' => $this->faker->words(asText: true),
            'cityAlt' => $this->faker->city(),
            'address' => $this->faker->address(),
            'zipcode' => $this->faker->postcode(),
            'tel' => $this->faker->phoneNumber(),
            'web' => $this->faker->url(),
            'webStatus' => 1,
            'combinedRating' => $this->faker->numberBetween(80, 99),
            'combinedRatingCount' => $this->faker->randomDigit(),
            'verified' => Listing::$statusOptions['ok'],
            'onlineReservations' => 1,
            'compiledFeatures' => [
                'goodFor' => $this->faker->randomElements(
                    ['adventure_hostels', 'beach_hostels', 'business', 'couples', 'families', 'female_solo_traveller', 'groups', 'partying', 'quiet', 'seniors', 'socializing', 'youth_hostels'],
                    $this->faker->numberBetween(1, 5)
                ),
            ],
        ];
    }

    public function forCity(CityInfo $city)
    {
        return Listing::factory()->state([
            'continent' => $city->continent,
            'country' => $city->country,
            'region' => $city->region,
            'city' => $city->city,
        ]);
    }
}
