<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PicsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'subjectID' => $this->faker->numberBetween(1, 100),
            'subjectType' => $this->faker->word,
            'type' => $this->faker->word,
            'source' => $this->faker->word,
            'picNum' => $this->faker->unique()->randomNumber(),
            'isPrimary' => $this->faker->boolean,
            'featuredPhoto' => $this->faker->boolean,
            'originalFiletype' => $this->faker->fileExtension,
            'originalWidth' => $this->faker->numberBetween(100, 2000),
            'originalHeight' => $this->faker->numberBetween(100, 2000),
            'originalAspect' => $this->faker->randomFloat(2, 0.5, 2.0),
            'originalFilesize' => $this->faker->numberBetween(1000, 1000000),
            'originalMD5hash' => $this->faker->md5,
            'imageSearchCheckDate' => $this->faker->optional()->date,
            'imageSearchMatches' => $this->faker->text,
            'caption' => $this->faker->sentence,
            'edits' => $this->faker->sentence,
            'storageTypes' => $this->faker->sentence,
            'lastUpdate' => $this->faker->optional()->date,
        ];
    }
}
