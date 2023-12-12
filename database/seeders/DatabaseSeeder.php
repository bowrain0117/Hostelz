<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        throw_unless(
            app()->isLocal(),
            \Exception::class,
            sprintf('Configuration Error: Expected \'APP_ENV\' to be set to \'local\', but found \'%s\' instead.', app()->environment())
        );

        $this->call([
            UserSeeder::class,
            CitySeeder::class,
            ListingSeeder::class,
        ]);
    }
}
