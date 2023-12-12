<?php

namespace App\Jobs\Imported;

use App\Models\Imported;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportedStatusUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Imported $imported)
    {
    }

    public function handle(): void
    {
        Log::channel('importedStatusUpdate')->info('Imported status changed to inactive', [
            'importedID' => $this->imported->id,
        ]);

        $listing = $this->imported->listing;

        if (isset($listing) && $listing->activeImporteds->isEmpty()) {
            return;
        }

        $listing->activeImporteds->each(function (Imported $imported) use ($listing) {
            if (
                $imported->getImportSystem()->isPreferredBookingSystem &&
                ($imported->name === $imported->previousName) &&
                ($listing->name !== $imported->previousName)
            ) {
                $imported->previousName = $listing->name;
                $imported->saveQuietly();
            }
        });
    }
}
