<?php

namespace Database\Factories;

use App\Models\AttachedText;
use App\Models\Languages;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttachedText>
 */
class AttachedTextFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subjectType' => $this->faker->randomElement(AttachedText::$subjectTypeOptions),
            'subjectID' => $this->faker->randomDigit(),
            'subjectString' => $this->faker->words(asText: true),
            'type' => $this->faker->randomElement(['']),
            'source' => $this->faker->randomElement(['']),
            'language' => Languages::DEFAULT_LANG_CODE,
            'status' => $this->faker->randomElement(AttachedText::$statusOptions),
            'score' => $this->faker->numberBetween(50, 100),
            'data' => $this->faker->realText(),
            'userID' => User::factory(),
        ];
    }
}
