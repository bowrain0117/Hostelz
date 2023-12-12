<?php

namespace Database\Factories;

use App\Models\ImportHistory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ImportHistoryFactory extends Factory
{
    protected $model = ImportHistory::class;

    public function definition(): array
    {
        return [
            'system' => $this->faker->word(),
            'checked' => $this->faker->randomNumber(),
            'inserted' => $this->faker->randomNumber(),
            'options' => $this->faker->word(),
            'started_at' => Carbon::now(),
            'cancelled_at' => Carbon::now(),
            'finished_at' => Carbon::now(),
        ];
    }
}
