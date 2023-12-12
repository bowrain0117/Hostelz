<?php

namespace App\Jobs\Import;

use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\RateLimitedMiddleware\RateLimited;

class ImportFromPageBookHostelsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 7;

    public function __construct(
        public int $page
    ) {
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        ImportBookHostels::importPage($this->page);
    }

    public function middleware(): array
    {
        $rateLimitedMiddleware = (new RateLimited())
            ->allow(10)
            ->everySeconds(60)
            ->releaseAfterSeconds(11);

        return [$rateLimitedMiddleware];
    }

    public function backoff()
    {
        return [60, 60 * 5, 60 * 20, 60 * 60, 60 * 60];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addWeek();
    }
}
