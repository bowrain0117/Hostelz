<?php

namespace App\Console\Commands;

use App\Models\Imported;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateListingsFromImporteds extends Command
{
    protected $signature = 'hostelz:createListingsFromImporteds';

    protected $description = 'Create new listings from imported data.';

    public function handle(): void
    {
        $imported = Imported::where([
            ['hostelID', 0],
            ['country', '!=', ''],
            ['propertyType', '!=', ''],
            ['status', 'active'],
        ]);

        Log::info("createListingsFromImporteds start: {$imported->count()}");

        $imported
            ->lazyById()
            ->each->createListing();

        Log::info('createListingsFromImporteds finish');
    }
}
