<?php

namespace Database\Factories;

use App\Models\ContinentInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'city' => $this->faker->city,
            'continent' => collect(ContinentInfo::allNames())->random(),
            'country' => $this->faker->country,
            'region' => $this->faker->words(asText: true),
            'totalListingCount' => $this->faker->randomDigitNotZero(),
            'displaysRegion' => true,
        ];
    }
}
