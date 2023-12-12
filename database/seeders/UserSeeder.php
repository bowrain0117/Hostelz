<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(2)
            ->state(new Sequence(
                [
                    'name' => 'Admin',
                    'slug' => 'admin',
                    'username' => 'admin@test.com',
                    'passwordHash' => Hash::make('admin'),
                    'access' => implode(',', User::$accessOptions),
                ],
                [
                    'name' => 'Editor',
                    'slug' => 'editor',
                    'username' => 'editor@test.com',
                    'passwordHash' => Hash::make('editor'),
                    'access' => 'staff,staffWriter',
                ],
            ))
            ->create();

        User::factory()
            ->count(10)
            ->create();
    }
}
