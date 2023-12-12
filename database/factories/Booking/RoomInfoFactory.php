<?php

namespace Database\Factories\Booking;

use App\Booking\RoomInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomInfoFactory extends Factory
{
    protected $model = RoomInfo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'code' => $this->faker->numerify('#####'),
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement([RoomInfo::TYPE_DORM, RoomInfo::TYPE_PRIVATE]),
            'sex' => $this->faker->randomElement(['female', 'male', 'mixed']),
            'ensuite' => $this->faker->boolean(),
            'peoplePerRoom' => $this->faker->numberBetween(1, 3),
            'bedsPerRoom' => $this->faker->numberBetween(1, 3),
        ];
    }
}
