<?php

namespace Database\Factories;

use App\Models\Pic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PicFactory extends Factory
{
    protected $model = Pic::class;

    public function definition(): array
    {
        return [
            'status' => $this->faker->word(),
            'subjectID' => $this->faker->randomNumber(),
            'subjectType' => $this->faker->word(),
            'type' => $this->faker->word(),
            'source' => $this->faker->word(),
            'picNum' => $this->faker->randomNumber(),
            'isPrimary' => $this->faker->boolean(),
            'featuredPhoto' => $this->faker->boolean(),
            'originalFiletype' => $this->faker->word(),
            'originalWidth' => $this->faker->randomNumber(),
            'originalHeight' => $this->faker->randomNumber(),
            'originalAspect' => $this->faker->randomFloat(),
            'originalFilesize' => $this->faker->randomNumber(),
            'originalMD5hash' => $this->faker->word(),
            'imageSearchCheckDate' => Carbon::now(),
            'imageSearchMatches' => $this->faker->word(),
            'caption' => $this->faker->word(),
            'edits' => $this->faker->word(),
            'storageTypes' => 'local',
            'lastUpdate' => Carbon::now(),
        ];
    }
}
