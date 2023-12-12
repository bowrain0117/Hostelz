<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\CityGroup;
use App\Models\Country;
use App\Models\Listing\Listing;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CreateIndexes extends Command
{
    // models we use in our indexes
    public const MODEL_INDEXES = [Listing::class, City::class, Region::class, CityGroup::class, Country::class];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostelz:makeIndex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates all the necessary indexes with a single command';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        foreach (self::MODEL_INDEXES as $model) {
            Artisan::call(
                'scout:import',
                ['model' => $model]
            );
            $this->info("Successfully created index for {$model} model");
        }
    }
}
