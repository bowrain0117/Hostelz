<?php

namespace Database\Factories;

use App\Models\ContinentInfo;
use App\Models\CountryInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CountryInfo>
 */
class CountryInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'country' => $this->faker->country,
            'continent' => collect(ContinentInfo::allNames())->random(),
            'regionType' => collect(CountryInfo::$regionTypeOptions)->random(),
            'currencyCode' => $this->faker->currencyCode,
            'geonamesCountryID' => '',
            'cityCount' => $this->faker->randomDigit(),
            'notes' => '',
        ];
    }
}
