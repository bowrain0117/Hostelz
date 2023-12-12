<?php

namespace App\Console;

use App\Console\Commands\CheckDiskSize;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\WebsiteMaintenance;
use Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\WebsiteMaintenance::class,
        \App\Console\Commands\LocalDev::class,
        \App\Console\Commands\BookingAvailability::class,
        \App\Console\Commands\ImportedImport::class,
        \App\Console\Commands\RunTempFunction::class,
        Commands\GenerateSitemap::class,
        Commands\CheckDiskSize::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // This resets the files created by withoutOverlapping() just in case it never got deleted (if it crashed for example)
        foreach (glob(storage_path('framework/schedule-*')) as $filename) {
            $timestamp = @filemtime($filename);
            if (! $timestamp) {
                continue;
            } // apparently the file was getting deleted between our glob() and our filemtiem() in some cases.
            if (Carbon::createFromTimestamp($timestamp)->diffInDays() >= 1) {
                @unlink($filename);
                logError("A withoutOverlapping() file was found older than 1 day ('$filename').");
            }
        }

        //  at 00:20
        $schedule->command(GenerateSitemap::class)
                 ->weeklyOn(7, '16:20')
                 ->sendOutputTo('/tmp/hostelz-schedule-output-queue-sitemap');

        $schedule->command(WebsiteMaintenance::class, ['tenMinute'])
                 ->everyTenMinutes()
                 ->withoutOverlapping()
                 ->sendOutputTo('/tmp/hostelz-schedule-output-tenMinute');

        $schedule->command(WebsiteMaintenance::class, ['hourly'])
                 ->hourly()
                 ->withoutOverlapping()
                 ->sendOutputTo('/tmp/hostelz-schedule-output-hourly');

        $schedule->command(WebsiteMaintenance::class, ['daily'])
                 ->dailyAt('03:30')
                 ->sendOutputTo('/tmp/hostelz-schedule-output-daily');

        $schedule->command(WebsiteMaintenance::class, ['weekly'])
                 ->weekly()
                 ->sendOutputTo('/tmp/hostelz-schedule-output-weekly');

        $schedule->command(WebsiteMaintenance::class, ['monthly'])
                 ->monthly()
                 ->sendOutputTo('/tmp/hostelz-schedule-output-monthly');

        $schedule->command(CheckDiskSize::class)
                 ->hourly()
                 ->withoutOverlapping()
                 ->appendOutputTo('/tmp/hostelz-schedule-output-CheckDiskSize');

        $schedule->command('telescope:prune --hours=48')->daily();

        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        $schedule->command('queue:prune-batches --hours=48')->daily();

        $schedule->command('model:prune')->daily();

        $schedule->command('domain-parser:refresh')->weekly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
