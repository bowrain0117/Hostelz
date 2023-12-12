<?php

namespace App\Observers;

use App\Jobs\Imported\CutPicsCountImportedJob;
use App\Jobs\Imported\ImportedStatusUpdateJob;
use App\Models\Imported;
use Illuminate\Support\Facades\Log;

class ImportedObserver
{
    public function updated(Imported $imported): void
    {
        if ($imported->isDirty('status') && $imported->status === Imported::STATUS_INACTIVE) {
            ImportedStatusUpdateJob::dispatch($imported);
            CutPicsCountImportedJob::dispatch($imported);
        }
    }

    public function deleting(Imported $imported): void
    {
        Log::channel('importedOutdated')
            ->info('Deleting of imported', [
                'imported_id' => $imported->id,
                'system' => $imported->system,
                'hostel_id' => $imported->hostelID,
                'status' => $imported->status,
                'nowTime' => now(),
                'updated_at' => $imported->updated_at,
                'created_at' => $imported->created_at,
            ]);
    }
}
