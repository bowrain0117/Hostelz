<?php

namespace Database\Factories;

use App\Enums\CategorySlp;
use App\Enums\StatusSlp;
use App\Models\CityInfo;
use App\Models\Languages;
use App\Models\SpecialLandingPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpecialLandingPage>
 */
class SpecialLandingPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'subjectable_type' => CityInfo::class,
            'subjectable_id' => CityInfo::factory(),
            'status' => StatusSlp::Draft->value,
            'language' => Languages::DEFAULT_LANG_CODE,
            'number_featured_hostels' => SpecialLandingPage::FEATURED_HOSTELS,
            'title' => $this->faker->sentence,
            'meta_title' => $this->faker->sentence,
            'meta_description' => $this->faker->paragraph,
            'slug' => $this->faker->slug,
            'content' => $this->faker->paragraphs(3, true),
            'notes' => $this->faker->paragraphs(2, true),
            'category' => $this->faker->randomElement([CategorySlp::Private, CategorySlp::Best]),
        ];
    }
}
