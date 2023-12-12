<?php

namespace App\Console\Commands;

use App\Models\Listing\Listing;
use Illuminate\Console\Command;

class RemoveTestImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostelz:removeTests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all test listings';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $listings = Listing::getTestListings();

        if ($listings->isEmpty()) {
            $this->info('No test listings found');

            return;
        }

        $listings->each(function ($listing): void {
            $listing->update(['verified' => Listing::$statusOptions['unlisted']]);
            $this->info('Listing ' . $listing->name . ', ' . $listing->country . ', ' . $listing->city . ' has changed verified type to unlisted');
        });

        $this->info('All test listings successfully changed verified type to unlisted');
    }
}
