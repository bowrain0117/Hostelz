<?php

namespace App\Console\Commands;

/*
Usage:

    php artisan hostelz:websiteMaintenance hourly
    php artisan hostelz:websiteMaintenance daily
    php artisan hostelz:websiteMaintenance weekly
    php artisan hostelz:websiteMaintenance monthly
    php artisan hostelz:websiteMaintenance afterListingDataImport
*/

use App\Helpers\MailFetch;
use App\Models\Ad;
use App\Models\AttachedText;
use App\Models\Booking;
use App\Models\BookingClick;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\IncomingLink;
use App\Models\LanguageString;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingDuplicate;
use App\Models\MailMessage;
use App\Models\Pic;
use App\Models\PriceHistory;
use App\Models\Review;
use App\Models\User;
use App\Services\ImportSystems\ImportSystems;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lib\DataCorrection;
use Lib\FileUploadHandler;
use Lib\Geocoding;
use Lib\PageCache;

class WebsiteMaintenance extends Command
{
    protected $signature = 'hostelz:websiteMaintenance {period}'; // 'tenMinute/hourly/daily/weekly/monthly'

    protected $description = 'Our own maintenance scripts.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        DB::disableQueryLog(); // to save memory

        switch ($this->argument('period')) {
            case 'tenMinute':
                $this->outputTitle('Mail Messages');
                $output = MailMessage::maintenanceTasks('tenMinute');
                $this->info($output);

                $this->outputTitle('Mail Fetch');
                $output = MailFetch::maintenanceTasks('tenMinute');
                $this->info($output);

                $this->outputTitle(''); // output final time elapsed.

                break;

            case 'hourly':
                $this->outputTitle('Start hourly maintenanceTasks');

                $this->outputTitle('ImportSystems');
                $output = ImportSystems::maintenanceTasks('hourly');
                $this->info($output);

                $this->outputTitle('Listings');
                $output = Listing::maintenanceTasks('hourly');
                $this->info($output);

                $this->outputTitle('IncomingLinks');
                $output = IncomingLink::maintenanceTasks('hourly');
                $this->info($output);

                $this->outputTitle('Finish hourly maintenanceTasks'); // output final time elapsed.

                break;

            case 'daily':
                $this->outputTitle('Start daily maintenanceTasks');

                $this->outputTitle('Mail Messages');
                $output = MailMessage::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('Ads');
                $output = Ad::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('Attached Text');
                $output = AttachedText::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('FileUploadHandler');
                $output = FileUploadHandler::dailyMaintenance();
                $this->info($output);

                $this->outputTitle('Listings');
                $output = Listing::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('Listing Duplicates');
                $output = ListingDuplicate::dailyMaintenance();
                $this->info($output);

                $this->outputTitle('CityInfo');
                $output = CityInfo::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('Reviews');
                $output = Review::maintenanceTasks('daily');
                $this->info($output);

                // (do this one to import bookings before Booking sends After-Stay Emails)
                $this->outputTitle('ImportSystems');
                $output = ImportSystems::maintenanceTasks('daily');
                $this->info($output);

                // (do this one to fill in missing info for the bookings before Booking sends After-Stay Emails)
                $this->outputTitle('BookingClicks');
                $output = BookingClick::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('Bookings');
                $output = Booking::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('IncomingLinks');
                $output = IncomingLink::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('Users');
                $output = User::maintenanceTasks('daily');
                $this->info($output);

                $this->outputTitle('PageCache');
                $output = PageCache::maintenanceTasks();
                $this->info($output);

                $this->outputTitle('Finish daily maintenanceTasks'); // output final time elapsed.

                break;

            case 'weekly':
                $this->outputTitle('Start weekly maintenanceTasks');

                $this->outputTitle('Mail Messages');
                $output = MailMessage::maintenanceTasks('weekly');
                $this->info($output);

                $this->outputTitle('Listings');
                $output = Listing::maintenanceTasks('weekly');
                $this->info($output);

                $this->outputTitle('Pics');
                $output = Pic::maintenanceTasks('weekly');
                $this->info($output);

                $this->outputTitle('CityInfo');
                $output = CityInfo::maintenanceTasks('weekly');
                $this->info($output);

                $this->outputTitle('CountryInfo');
                $output = CountryInfo::maintenanceTasks('weekly');
                $this->info($output);

                $this->outputTitle('Reviews');
                $output = Review::maintenanceTasks('weekly');
                $this->info($output);

                /* disabling for now until they make BackupManager work with flysystem-aws-s3-v3 and add version management to the backups
                $this->outputTitle("Database Backup");
                $manager = App::make(\BackupManager\Manager::class);
                $manager->makeBackup()->run('mysql', 's3', 'backup.sql', 'gzip');
                */

                $this->call(RemoveTestImports::class);

                $this->outputTitle('Finish weekly maintenanceTasks'); // output final time elapsed.

                break;

            case 'monthly': // (this should only be used for tasks that shouldn't instead by done by 'afterListingDataImport')
                $this->outputTitle('Start monthly maintenanceTasks');

                $this->outputTitle('Geocoding');
                $output = Geocoding::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('DataCorrection');
                $output = DataCorrection::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('Listings');
                $output = Listing::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('CityInfo');
                $output = CityInfo::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('IncomingLinks');
                $output = IncomingLink::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('Users');
                $output = User::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('Reviews');
                $output = Review::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('PriceHistory');
                $output = PriceHistory::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('LanguageString');
                $output = LanguageString::maintenanceTasks('monthly');
                $this->info($output);

                $this->outputTitle('Finish monthly maintenanceTasks'); // output final time elapsed.

                break;

            case 'afterListingDataImport':
                $this->outputTitle('Start afterListingDataImport');

                $this->outputTitle('Listings');
                $output = Listing::maintenanceTasks('afterListingDataImport');
                $this->info($output);

                $this->outputTitle('CityInfo');
                // (must be done *after* Listings maintenance)
                $output = CityInfo::maintenanceTasks('afterListingDataImport');
                $this->info($output);

                $this->call(RemoveTestImports::class);

                $this->outputTitle('Finish afterListingDataImport'); // output final time elapsed.

                break;

            default:
                $this->error('Unknown time period "' . $this->argument('period') . '".');
        }
    }

    public function info($string, $verbosity = null): void
    {
        Log::channel('websiteMaintenance')->info($string);

        parent::info($string, $verbosity);
    }

    private function outputTitle($s): void
    {
        $this->info(($s !== '' ? "* $s *" : '') . ' (' . $this->elapsedTime() . 's)' . "\n");
    }

    private function elapsedTime()
    {
        static $lastTime = 0;

        $now = time();
        $difference = ($lastTime ? $now - $lastTime : 0);
        $lastTime = $now;

        return $difference;
    }
}
