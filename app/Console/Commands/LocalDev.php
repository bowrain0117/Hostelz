<?php

namespace App\Console\Commands;

/*
Usage:

    php artisan hostelz:localDev partialDatabaseDump

*/

use Config;
use Illuminate\Console\Command;

class LocalDev extends Command
{
    protected $signature = 'hostelz:localDev {subcommand}';

    protected $description = 'Local (offline) dev support scripts.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        switch ($this->argument('subcommand')) {
            case 'partialDatabaseDump':

                $tables = [
                    'adStats' => '1 LIMIT 1000',
                    'ads' => '1 LIMIT 1000',
                    'altNames' => '1 LIMIT 1000',
                    'articles' => '1 LIMIT 1000',
                    'attached' => '1 LIMIT 1000',
                    'bayesianFilter' => '1 LIMIT 1000',
                    'bookingCache' => '1 LIMIT 1000',
                    'bookings' => '1 LIMIT 2000',
                    'chat' => '1 LIMIT 1000',
                    'cityComments' => '1 LIMIT 1000',
                    'cityInfo' => '1 LIMIT 1000',
                    'comments' => '1 LIMIT 1000',
                    'countries' => '1 LIMIT 1000',
                    'geonamesCountries' => '1 LIMIT 1000',
                    'dataCorrection' => '1 LIMIT 1000',
                    'duplicates' => '1 LIMIT 1000',
                    'eventLog' => '1 LIMIT 1000',
                    'experimentData' => '1 LIMIT 1000',
                    'experiments' => '1 LIMIT 1000',
                    'geocodeCache' => '1 LIMIT 100',
                    'geonames' => '1 LIMIT 1000',
                    'listings' => '1 LIMIT 2000',
                    'imported' => '1 LIMIT 3000',
                    'incomingLinks' => '1 LIMIT 1000',
                    'ipToCountry' => '1 LIMIT 1000',
                    'languageStrings' => '1 LIMIT 1000',
                    'links' => '1 LIMIT 1000',
                    'macros' => '1 LIMIT 1000',
                    'mail' => '1 LIMIT 1000',
                    'mailAttachment' => '1 LIMIT 1000',
                    'migrations' => '1 LIMIT 1000',
                    'pageCacheIndex' => '1 LIMIT 100',
                    'pics' => '1 LIMIT 1000',
                    'poll' => '1 LIMIT 1000',
                    'pollData' => '1 LIMIT 1000',
                    'polls' => '1 LIMIT 1000',
                    'priceHistory' => '1 LIMIT 100',
                    'questionResults' => '1 LIMIT 100',
                    'questionSets' => '1 LIMIT 1000',
                    'rankings' => '1 LIMIT 1000',
                    'reviews' => '1 LIMIT 1000',
                    'savedList' => '1 LIMIT 1000',
                    'savedListListings' => '1 LIMIT 1000',
                    'socialMsg' => '1 LIMIT 100',
                    'spiderResults' => '1 LIMIT 1000',
                    'sprites' => '1 LIMIT 1000',
                    'redirects' => '1 LIMIT 100',
                    'wishlists' => '1 LIMIT 100',
                    'listing_wishlist' => '1 LIMIT 100',
                    'hostels_chains' => '1 LIMIT 100',
                    'users_dream_destinations' => '1 LIMIT 100',
                    'users_favorite_hostels' => '1 LIMIT 100',
                    'users_search_histories' => '1 LIMIT 100',
                    'users' => '1 LIMIT 2000',
                ];

                $dumpCommand = 'mysqldump  --quick --add-drop-table --compact' .
                    ' --user=' . Config::get('database.connections.mysql.username') .
                    ' --password=' . Config::get('database.connections.mysql.password') . ' ' .
                    Config::get('database.connections.mysql.database');

                foreach ($tables as $table => $where) {
                    passthru($dumpCommand . ' ' . $table . ($where != '' ? " --where=\"$where\"" : ''));
                }

                break;

            default:
                $this->error('Unknown subcommand "' . $this->argument('subcommand') . '".');
        }
    }
}
