<?php

namespace App\Jobs\Imported;

use App\Models\Imported;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\RateLimitedMiddleware\RateLimited;

class DownloadPicsImportedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Imported $imported
    ) {
    }

    public function handle(): void
    {
        $this->imported->downloadPics();
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
        return now()->addDay();
    }
}
