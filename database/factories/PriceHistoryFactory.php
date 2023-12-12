<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PriceHistoryFactory extends Factory
{
    public function definition()
    {
        return [
            //            'listingID' => $this->faker->city,
            'month' => $this->faker->date(),
            'roomType' => $this->faker->randomElement(['dorm', 'private']),
            'peoplePerRoom' => $this->faker->randomDigit(),
            'averagePricePerNight' => $this->faker->randomFloat(2, 1, 200),
            'dataPointsInAverage' => $this->faker->randomDigitNotZero(),
        ];
    }
}
