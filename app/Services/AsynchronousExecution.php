<?php

namespace App\Services;

use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

class AsynchronousExecution
{
    public static function executeArtisanCommandsInParallel($commands)
    {
        $pool = Process::pool(function (Pool $pool) use ($commands) {
            collect($commands)->each(
                fn ($command, $key) => $pool->as($key)
                    ->path(base_path())
                    ->command(self::artisanCommand($command))
            );
        });

        return $pool->wait()
            ->collect()
            ->mapWithKeys(
                fn ($item, $key) => [
                    $key => unserialize(base64_decode($item->output())),
                ]
            )
            ->toArray();
    }

    public static function artisanCommand($parameters): string
    {
        return PHP_BINDIR . '/php ' . base_path() . '/artisan ' . $parameters;
    }
}
