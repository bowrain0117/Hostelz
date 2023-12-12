<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'username' => $this->faker->unique()->safeEmail,
            'passwordHash' => Hash::make($this->faker->password()),
            'access' => '',
            'nickname' => '',
            'slug' => '',
            'status' => 'ok',
            'bio' => '',
            'data' => '',
            'isPublic' => '',
            'gender' => '',
            'dateAdded' => date('Y-m-d'),
        ];
    }
}
