<?php

namespace Database\Factories;

use App\Enums\District\Type;
use App\Models\CityInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistrictFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cityId' => CityInfo::factory(),
            'name' => $this->faker->city,
            'type' => Type::In->value,
            'slug' => $this->faker->unique()->slug,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'description' => $this->faker->realText(),
            'is_active' => true,
            'is_city_centre' => $this->faker->boolean,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
