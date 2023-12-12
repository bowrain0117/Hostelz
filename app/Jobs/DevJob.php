<?php

namespace App\Jobs;

use App\Models\Imported;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Spatie\RateLimitedMiddleware\RateLimited;

class DevJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $importedId
    ) {
    }

    public function handle()
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Imported::find($this->importedId)?->downloadPics(true);
    }

    public function middleware(): array
    {
        $rateLimitedMiddleware = (new RateLimited())
            ->allow(2)
            ->everySeconds(29)
            ->releaseAfterSeconds(15);

        return [$rateLimitedMiddleware];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addDays(10);
    }
}
