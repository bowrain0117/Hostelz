<?php

namespace App\Jobs\Imported;

use App\Models\Imported;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CutPicsCountImportedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Imported $imported
    ) {
    }

    public function handle(): void
    {
        if ($this->imported->picsObjects->count() <= Imported::INACTIVE_PICS_COUNT) {
            return;
        }

        $this->imported->picsObjects
            ->slice(3)
            ->each->delete();
    }
}
