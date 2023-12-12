<?php

namespace App\Jobs\Import;

use App\Services\ImportSystems\BookingDotCom\ImportBookingDotCom;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\SerializesModels;
use Spatie\RateLimitedMiddleware\RateLimited;

class ImportFromCityBookingDotComJob implements ShouldQueue
{
    use Dispatchable, Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public object $city,
        public string $country
    ) {
    }

    public function handle(ImportBookingDotCom $importSystem): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $importSystem->importFromCity(
            $this->city,
            $this->country,
        );
    }

    public function middleware(): array
    {
        $rateLimitedMiddleware = (new RateLimited())
            ->allow(20)
            ->everySeconds(10)
            ->releaseAfterSeconds(11);

        return [$rateLimitedMiddleware];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addWeek();
    }
}
