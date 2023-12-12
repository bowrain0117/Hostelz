<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subjectable_type' => $this->faker->randomElement([District::class]),
            'subjectable_id' => $this->faker->randomNumber(5),
            'question' => $this->faker->text(5100),
            'answer' => $this->faker->paragraph,
            'created_at' => $this->faker->dateTime,
            'updated_at' => $this->faker->dateTime,
        ];
    }
}
