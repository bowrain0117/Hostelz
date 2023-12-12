<?php

namespace App\Console\Commands;

use App;
use App\Booking\SearchCriteria;
use App\Jobs\DevJob;
use App\Jobs\Imported\CutPicsCountImportedJob;
use App\Models\CityInfo;
use App\Models\District;
use App\Models\Imported;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingDuplicate;
use App\Models\Listing\ListingMaintenance;
use App\Models\MailAttachment;
use App\Models\Pic;
use App\Models\Review;
use App\Models\User;
use App\Services\ImportSystems\BookHostels\APIBookHostels;
use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use App\Services\ImportSystems\BookHostels\MaintenanceBookHostels;
use App\Services\ImportSystems\BookingDotCom\APIBookingDotCom;
use App\Services\ImportSystems\BookingDotCom\BookingDotComService;
use App\Services\ImportSystems\BookingDotCom\MaintenanceBookingDotCom;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Lib\ImageProcessor;
use Lib\PageCache;
use URL;

/*

Usage:

    cd /home/hostelz/dev; php artisan hostelz:runTempFunction ...

*/

class RunTempFunction extends Command
{
    protected $signature = 'hostelz:runTempFunction {arguments*}';

    protected $description = 'Run special code for special purposes.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $arguments = $this->argument('arguments');
        $functionName = array_shift($arguments);

        if ($arguments) {
            $this->$functionName($arguments);
        } else {
            $this->$functionName();
        }
    }

    private function outputTitle($s): void
    {
        $this->info("\n(" . $this->elapsedTime() . 's)' . ($s != '' ? " * $s *" : '') . "\n");
    }

    private function elapsedTime()
    {
        static $lastTime = 0;

        $now = time();
        $difference = ($lastTime ? $now - $lastTime : 0);
        $lastTime = $now;

        return $difference;
    }

    /* Functions */

    private function outputTest(): void
    {
        $this->info('test1');
        sleep(10);
        $this->info('test2');
    }

    private function clearAllPageCache(): void
    {
        PageCache::clearAll();
    }

    private function updateBookHostelsPics(): void
    {
        DB::disableQueryLog(); // to save memory
        set_time_limit(24 * 60 * 60);

        $importedIDs = Imported::where('system', 'BookHostels')->where('status', 'active')
            ->where('propertyType', 'hostel')
            ->where('pics', '')->where('hostelID', '!=', 0)->orderBy('id')->pluck('id');
        //dd($importedIDs->count());

        foreach ($importedIDs as $importedID) {
            echo "\n$importedID: ";

            $imported = Imported::findOrFail($importedID);

            if ($imported->listing->getBestPics()->count()) {
                echo 'has pics';

                continue;
            }

            $systemClassName = $imported->getImportSystemClassName();
            $systemClassName::tempPicsOnly($imported);

            if ($imported->pics) {
                $imported->save();
                echo ' ' . implode(', ', $imported->pics);
                $imported->downloadPics();
                with(new ListingMaintenance($imported->listing))->updateThumbnail();
                $imported->listing->clearRelatedPageCaches();
                echo ' downloaded.';
                sleep(5);
            }
        }
    }

    private function deletePicsFromInactiveImporteds(): void
    {
        DB::disableQueryLog(); // to save memory
        set_time_limit(5 * 60 * 60);

        $importedIDs = Imported::orderBy('id')->pluck('id'); // where('status', 'inactive')->

        foreach ($importedIDs as $importedID) {
            echo "$importedID: ";

            $imported = Imported::findOrFail($importedID);
            $pics = $imported->picsObjects;
            if ($pics->isEmpty()) {
                echo "No pics.\n";

                continue;
            }

            $listing = $imported->listing;
            if (! $listing) {
                echo "No listing!\n";

                continue;
            }

            if ($imported->status == 'active' && $listing->isLive()) {
                echo "Active imported for live listing.\n";

                continue;
            }

            if ($listing->propertyType != 'Hostel' && ! $listing->isLive()) {
                echo "Not hostel ($listing->propertyType) and not live. ";
            } else {
                $bestPics = $listing->getBestPics();
                $usingImportedPics = false;
                foreach ($bestPics as $pic) {
                    if ($pic->subjectType == 'imported' && $pic->subjectID == $imported->id) {
                        $usingImportedPics = true;

                        break;
                    }
                }
                if ($usingImportedPics) {
                    echo "Using these pics!\n";

                    continue;
                }
            }

            echo 'Deleting ';
            foreach ($pics as $pic) {
                echo $pic->id . ' ';
                $pic->delete();
            }
            echo "\n";
        }
    }

    private function queueTest(): void
    {
        dispatch(new \App\Jobs\MailAttachmentDelete(MailAttachment::first()));
    }

    private function setPicStorageTypes()
    {
        $ids = Pic::where('storageTypes', '')->pluck('id');
        // $ids = Pic::where('subjectType', 'ads')->pluck('id');

        foreach ($ids as $id) {
            echo "$id\n";

            $pic = Pic::findOrFail($id);

            $storageTypes = [];

            // size type without any size type ('')
            $files = glob($pic->localFilePath('', '*'));
            // size types of the pic
            $found = glob($pic->localFilePath('*', '*'));
            $files = array_merge($files, $found);

            foreach ($files as $file) {
                echo "$file ";
                $prefix = $GLOBALS['PUBLIC_PATH_DYNAMIC_DATA'] . '/' . Pic::FOLDER . '/' . $pic->subjectType . '/' . ($pic->type != '' ? $pic->type . '/' : '');
                if (strpos($file, $prefix) !== 0) {
                    throw new Exception("Couldn't find $prefix in $file.");
                }

                $partialPath = str_replace($prefix, '', $file);
                $parts = explode('/', $partialPath);

                if (count($parts) == 3) {
                    $sizeType = $parts[0];
                } elseif (count($parts) == 2) {
                    $sizeType = ''; // empty size type
                } else {
                    throw new Exception("Error decoding $file.");
                }

                if (is_link($file)) {
                    // File::delete($file);
                    echo "(link)\n";

                    continue;
                }

                $storageTypes[$sizeType] = 'local';
                echo "\n";
            }

            echo ' -> ' . json_encode($storageTypes) . "\n";

            if (! $storageTypes) {
                throw new Exception('No storage types.');
            }

            $pic->storageTypes = $storageTypes;
            $pic->save();
        }

        return 'done.';
    }

    private function movePicsToCloud()
    {
        // Make the storage folders
        // Pic::make100DirsCloud('hostels', 'panorama', [ 'originals' ]);
        // Pic::make100DirsCloud('hostels', 'owner', [ 'originals' ]);
        // Pic::make100DirsCloud('reviews', '', [ 'originals' ]);

        $ids = Pic::where('storageTypes', '!=', '')
            ->whereIn('subjectType', ['hostels', 'reviews']) // only these are currently being moved to cloud storage
            ->where('storageTypes', 'not like', '%privateCloud%')->pluck('id');

        foreach ($ids as $id) {
            echo "$id ";

            $pic = Pic::findOrFail($id);

            foreach ($pic->storageTypes as $sizeType => $storageType) {
                if ($storageType != 'local') {
                    continue;
                }

                if (($pic->subjectType == 'hostels' && $pic->type == 'owner' && $sizeType == 'originals') ||
                    ($pic->subjectType == 'hostels' && $pic->type == 'panorama' && $sizeType == 'originals') ||
                    ($pic->subjectType == 'reviews' && $sizeType == 'originals')) {
                    echo "($sizeType move to cloud)";
                    $pic->changeStorageType($sizeType, 'privateCloud');
                }
            }

            echo "\n";
        }

        return 'done.';
    }

    private function createBigPicsForReviewPics(): void
    {
        echo "\ndo after: chown -h -R www-data.www-data /mnt/storage1/hostelz/pics\n\n";

        $picIDs = Pic::where('subjectType', 'reviews')->where('status', 'markedForEditing')->orderBy('id')->pluck('id');

        foreach ($picIDs as $picID) {
            $pic = Pic::find($picID);

            $image = ImageProcessor::makeFromString($pic->getImageData('originals'));
            if (! $image) {
                throw new Exception("Couldn't load image for $pic->id.");
            }

            // echo "<a href=\"".routeURL('staff-pics', $pic->id)."\"><img src=\"".$pic->url('')."\">$pic->id</a><br>";
            echo $pic->id . "\n";

            switch ($pic->subjectType) {
                case 'cityInfo':
                    $picOutputTypes = CityInfo::picFixPicOutputTypes();

                    break;

                case 'reviews':
                    $picOutputTypes = Review::picFixPicOutputTypes();

                    break;

                default:
                    throw new Exception("Unknown subjectType $subjectType.");
            }

            $edits = $pic->edits;
            if ($edits) {
                foreach ($picOutputTypes as &$picOutputType) {
                    $picOutputType = $picOutputType + $edits;
                }
            }
            //dd($picOutputTypes);
            $pic->saveImageFiles($image, $picOutputTypes);
            $image->releaseResources();
            //header('Content-Type:image/jpeg');
            //echo $image->getImageData();
        }
    }

    private function testImageEdits($arguments): void
    {
        return;

        $picID = array_shift($arguments);

        $pic = Pic::where('id', $picID)->get()->first();

        _d($pic->localFilePath('originals'));
        _d($pic->localFilePath('thumbnails'));

        $image = new Imagick($pic->localFilePath('originals'));

//        $image->setImageCompressionQuality(100);
        $image->setImageResolution(72, 72);
        $image->thumbnailImage(100, 100);

        $image->writeImage($pic->localFilePath('thumbnails'));

        _d($image->getImageResolution());

        dd('----');
    }

    private function testRecreateUsersThumbnail(): void
    {
        return;

        DB::disableQueryLog(); // to save memory
        set_time_limit(5 * 60 * 60);

        Pic::where('subjectType', 'users')
            ->orderBy('id')
            ->chunk(100, function ($pics) {
                foreach ($pics as $pic) {
                    echo $pic->id . "\n";

                    $picOutputTypes = [
                        'originals' => [],
                        'thumbnails' => [
                            'saveAsFormat' => 'jpg',
                            'outputQuality' => 75,
                            'absoluteWidth' => 100,
                            'absoluteHeight' => 100,
                            'cropVerticalPositionRatio' => 0.2,
                        ],
                    ];

                    $image = ImageProcessor::makeFromString($pic->getImageData('thumbnails'));
                    if (! $image) {
                        echo "Couldn't load thumbnails image for $pic->id.";

                        return true;
                    }

                    $dimensions = $image->getImageDimensions();
                    if ($dimensions['width'] === 100) {
                        echo "width 100\n";

                        return true;
                    }

                    $image = ImageProcessor::makeFromString($pic->getImageData('originals'));
                    if (! $image) {
                        echo "Couldn't load originals image for $pic->id.";

                        return true;
                    }

                    $pic->saveImageFiles($image, $picOutputTypes);
                    $image->releaseResources();

                    echo "done\n";
                }
            });

//            ->each(function ($pic, $key){
//
//            });
    }

    /* this can be called directly, or from https://dev-secure.hostelz.com/staff/temp/recreatePics in TempController.php */

    private function recreatePic($arguments): void
    {
        $picID = array_shift($arguments);

        echo "\ndo after: chown -h -R www-data.www-data /mnt/storage1/hostelz/pics\n\n";

        $pics = Pic::where('id', $picID)->get();

        foreach ($pics as $pic) {
            $image = ImageProcessor::makeFromString($pic->getImageData('originals')); // originals / thumbnails
            if (! $image) {
                throw new Exception("Couldn't load image for $pic->id.");
            }

//            echo "<a href=\"".routeURL('staff-pics', $pic->id)."\"><img src=\"".$pic->url('')."\">$pic->id</a><br>";
            echo $pic->id . "\n";

            switch ($pic->subjectType) {
                case 'cityInfo':
                    $picOutputTypes = CityInfo::picFixPicOutputTypes();

                    break;

                case 'reviews':
                    $picOutputTypes = Review::picFixPicOutputTypes();

                    break;

                case 'users':
                    $picOutputTypes = [
                        'originals' => [],
                        'thumbnails' => [
                            'saveAsFormat' => 'jpg',
                            'outputQuality' => 75,
                            'absoluteWidth' => 100,
                            'absoluteHeight' => 100,
                            'cropVerticalPositionRatio' => 0.2, /* If crop needed, crop closer to the top to avoid cutting off heads */
                        ],
                    ];

                    break;

                case 'hostelsChains':
                    $picOutputTypes = App\Models\HostelsChain::picFixPicOutputTypes();

                    break;

                default:
                    throw new Exception("Unknown subjectType $subjectType.");
            }

            $edits = $pic->edits;
            if ($edits) {
                foreach ($picOutputTypes as &$picOutputType) {
                    $picOutputType = $picOutputType + $edits;
                }
            }

            $pic->saveImageFiles($image, $picOutputTypes);
            $image->releaseResources();

            //header('Content-Type:image/jpeg');
            //echo $image->getImageData();
        }
    }

    private function testImageLoad()
    {
        $filename = 'http://ucd.hwstatic.com/propertyimages/2/269960/1.JPG';
        $filename = 'http://ucd.hwstatic.com/propertyimages/2/269960/15.gif';
        $originalImageData = @file_get_contents($filename);
        debugOutput('ImageProcessor file_get_contents done.');
        if ($originalImageData == '') {
            return null;
        }

        $imagick = new Imagick();
        //echo '1';
        try {
            echo 'result: ' . $imagick->readImageBlob($originalImageData) . '. ';
            //echo '2';
        } catch (Exception $e) {
            return null;
        }
        //echo '3';
        var_dump($imagick);
        echo 'format: ' . $imagick->getImageFormat() . '. ';
        echo 'size: ' . json_encode($imagick->getSize()) . '. ';
        echo 'prop: ' . json_encode($imagick->getImageProperties()) . '. ';
        echo ': ' . json_encode($imagick->getVersion()) . '. ';
        $t = $imagick->getImageGeometry();
        var_dump($t);

        $imagick->setBackgroundColor(new \ImagickPixel('#ffffff'));
        //$imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        //$this->imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        //$this->imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE); // progressie JPEG (better user experience, also required for panoramas under iOS 8)

        $imagick->setImageCompressionQuality(75);
        $imagick->stripImage(); // Strip out unneeded meta data
        // $imagick->writeImage($outputFilename);
    }

    private function setListingDuplicateMaxChoiceDifficulty(): void
    {
        DB::disableQueryLog(); // to save memory
        set_time_limit(5 * 60 * 60);

        $listingIDs = ListingDuplicate::where('status', 'suspected')->where('maxChoiceDifficulty', 0)->orderBy('listingID')->pluck('listingID')->unique();
        foreach ($listingIDs as $listingID) {
            $listing = Listing::find($listingID);
            echo "$listingID: " . ListingDuplicate::findDuplicates($listing, true, false) . "\n";
            echo '<br>request_terminate_timeout:' . (string) ini_get('request_terminate_timeout') . ' max_execution_time: ' . ini_get('max_execution_time');
            ListingDuplicate::where('listingID', $listingID)->where('status', 'suspected')->where('maxChoiceDifficulty', 0)->delete(); // delete any remaining old ones that we no longer think are duplicates
        }
        echo "\ndone.\n";
    }

    //  php artisan hostelz:runTempFunction testCron
    private function testCron(): void
    {
        echo date('Y-m-d H:i:s') . ' : testCron' . PHP_EOL;
    }

    //  php artisan hostelz:runTempFunction tetsImportSystemImport
    private function tetsImportSystemImport(): void
    {
        dump('BookingDotComService');
        BookingDotComService::import(
            [],
            function ($importedData, $b): void {
                dump($importedData);
            },
            null,
            true
        );
        dump('BookHostelsService');
        App\Services\ImportSystems\BookHostels\BookHostelsService::import(
            [],
            function ($importedData, $b): void {
                dump($importedData);
            },
            function ($output): void {
                dump($output);
            },
            true
        );
        dump('HostelsclubService');
        App\Services\ImportSystems\Hostelsclub\HostelsclubService::import(
            [],
            function ($importedData, $b): void {
                dump($importedData);
            },
            function ($output): void {
                dump($output);
            },
            true
        );
    }

    //  php artisan hostelz:runTempFunction testGetAvailability
    private function testGetAvailability(): void
    {
        $importeds = Imported::where([
            ['status', '=', 'active'],
            ['city', '=', 'Barcelona'],
        ]);

        $searchCriteria = new SearchCriteria(['startDate' => Carbon::now()->addDays(35), 'people' => 6, 'nights' => 1, 'roomType' => 'dorm', 'currency' => 'USD', 'language' => 'en']);

        $importedsCount = 10;

        collect([
            'BookingDotCom',
            'BookHostels',
            'Hostelsclub',
        ])->each(
            fn ($system) => dump(
                $system,
                collect(("App\\Services\\ImportSystems\\{$system}\\{$system}Service")::getAvailability(
                    (clone $importeds)->where('system', $system)->limit($importedsCount)->get(),
                    $searchCriteria,
                    false
                ))->map(fn ($item) => [$item->importedID, $item->bookingLinkInfo])->toArray()
            )
        );
    }

    //  php artisan hostelz:runTempFunction testHourlyMaintenance
    private function testHourlyMaintenance(): void
    {
        dump(BookingDotComService::hourlyMaintenance());
        dump(App\Services\ImportSystems\BookHostels\BookHostelsService::hourlyMaintenance());
    }

    //  php artisan hostelz:runTempFunction testUpdateDataForImported
    private function testUpdateDataForImported(): void
    {
        $imported = Imported::where([
            ['id', 2669760],
            ['status', '=', 'active'],
            ['system', '=', 'BookHostels'],
        ])->first();

        dump($imported->id);

        dump(App\Services\ImportSystems\BookHostels\BookHostelsService::updateDataForImported($imported));
    }

    //  php artisan hostelz:runTempFunction testRedirects
    private function testRedirects(): void
    {
//        App\Models\Redirect::select(['id', 'old_url', 'new_url', 'encoded_url'])->get()->each(
//            function($item) {
        ////                $item->old_url = Str::replace('www.hostelz.com', 'hostelz.test', $item->old_url);
//                $item->encoded_url = rawurlencode($item->old_url);
//                $item->save();
//            }
//        );
//
//        return;

        $client = Http::withOptions([
            'verify' => false,
            'allow_redirects' => false,

        ]);

        collect(App\Models\Redirect::select(['id', 'old_url', 'new_url', 'encoded_url'])->get())
            ->each(function ($item) use ($client): void {
                $response = $client->get($item->old_url);

                if (! $response->redirect()) {
                    dump($item->toArray());
                    dump($response->redirect());
                }
            });
    }

    //  php artisan hostelz:runTempFunction importCityInfoToCsv
    private function importCityInfoToCsv(): void
    {
        $items['cities'] = CityInfo::select(['city', 'region', 'country', 'totalListingCount'])
            ->orderBy('city')
            ->get()->toArray();

        $items['regions'] = CityInfo::select(['region', 'country', DB::raw('SUM(totalListingCount) as totalListingsRegion')])
            ->orderBy('totalListingsRegion', 'desc')
            ->groupBy('region')
            ->get()
            ->toArray();

        $items['countries'] = CityInfo::select(['country', DB::raw('SUM(totalListingCount) as totalListingsCountry')])
            ->orderBy('totalListingsCountry', 'desc')
            ->groupBy('country')
            ->get()
            ->toArray();

        foreach ($items as $key => $rows) {
            $fp = fopen(storage_path() . '/totalListings' . ucfirst($key) . '.csv', 'w');

            $header = match ($key) {
                'cities' => ['City', 'Region', 'Country', 'Total Listings Count'],
                'regions' => ['Region', 'Country', 'Total Listings Count'],
                'countries' => ['Country', 'Total Listings Count'],
            };

            fputcsv($fp, $header);

            foreach ($rows as $fields) {
                fputcsv($fp, $fields);
            }

            fclose($fp);
        }
    }

    //  php artisan hostelz:runTempFunction devCommand 10
    private function devCommand($arguments): void
    {
        $count = (int) array_shift($arguments);

        if (empty($count)) {
            Artisan::call('hostelz:runTempFunction devCommand 10');

            return;
        }

        $importeds = Imported::whereIn('system', ['BookHostels', 'BookingDotCom', 'Hostelsclub'])
            ->where('pics', '!=', '')
            ->whereRelation(
                'picsObjects', 'storageTypes', '[]'
            )
            ->orderBy('status');

        $total = $importeds->count();

        $importedsBatchJobs = $importeds
            ->take(10)
            ->get()
            ->map(function (Imported $imp, $key) {
                return new DevJob($imp->id);
            });

        Bus::batch($importedsBatchJobs)
            ->then(function (Batch $batch) use ($count) {
                sleep(10);

                Artisan::call('hostelz:runTempFunction devCommand ' . ($count - 1));
            })
            ->name("Upload Images batch: {$count}; total Importeds: {$total}")
            ->onQueue('import')
            ->dispatch();
    }

    //  php artisan hostelz:runTempFunction bdcImportsToCsv
    private function bdcImportsToCsv(): void
    {
        $importeds = Imported::where('system', 'BookingDotCom')
            ->where('propertyType', 'Hostel')
            ->where('status', Imported::STATUS_ACTIVE)
            ->orderBy('id')
//                             ->take(10)
            ->get()
            ->map(fn (Imported $item, $key) => [
                'id' => $key + 1,
                'bookingId' => $item->intCode,
                'name' => $item->name,
                'address' => $item->address1,
                'city' => $item->name,
                'country' => $item->name,
                'link' => $item->urlLink,
                'localId' => $item->id,
            ]);

        $header = ['id', 'Booking Id', 'Name', 'Address', 'City', 'Country', 'Link', 'Local Id'];

        createCsv('bdcItems', $importeds, $header);
    }

    //  php artisan hostelz:runTempFunction updateBdcRating
    private function updateBdcRating(): void
    {
        fastexcel()
            ->configureCsv(',')
            ->import(storage_path('app/bdc_ratings_1.csv'))
            ->each(function ($row, $key) {
                $overall = trim($row['Overall Score']);
                if ($overall === '') {
                    dump('error!!! overall empty ', $row);

                    return true;
                }

                $data = [
                    'overall' => $overall * 10 + random_int(-1, 2),
                ];

                $reviewsCount = str_replace(',', '', trim($row['Reviews Count']));
                if ($reviewsCount !== '') {
                    $data['count'] = (int) ($reviewsCount * (random_int(5, 9) / 10));
                }

                $cleanliness = trim($row['Cleanliness Subscore']);
                if ($cleanliness !== '') {
                    $data['cleanliness'] = (int) $cleanliness * 10 + random_int(-1, 2);
                }

                $staff = trim($row['Staff Subscore']);
                if ($staff !== '') {
                    $data['staff'] = (int) $staff * 10 + random_int(-1, 2);
                }

                $location = trim($row['Location Subscore']);
                if ($location !== '') {
                    $data['location'] = (int) $location * 10 + random_int(-1, 2);
                }

                $import = Imported::find($row['Local Id']);
                if ($import === null) {
                    dump('error!!! import id ' . $row['Local Id'] . 'not found');

                    return true;
                }

                dump($row);

                $import->update([
                    'rating' => $data,
                ]);

                if (empty($import->listing)) {
                    dump('error!!! not found listing for import id ' . $row['Local Id']);

                    return true;
                }
                $lm = ListingMaintenance::create($import->listing);

                dump($key, $lm->calculateCombinedRating());

                $lm->save();

//                if ($key > 100) {
//                    return false;
//                }
            });
    }

    //  php artisan hostelz:runTempFunction testHwLinks
    private function testHwLinks()
    {
        $items = Imported::select(['id', 'name', 'city', 'intCode', 'urlLink'])
            ->where([
                ['system', 'BookHostels'],
                ['status', 'active'],
            ])
//            ->limit(5000)
            ->get();

        ray($items->count());

//        ->lazyByIdDesc()
        $filtered = $items->filter(function (Imported $imported) {
            $res = preg_match(
                '|[^a-zA-Z0-9-_$%@&\s)(/.,+*!:#\|\'";]|',
                $imported->name
            );

            return $res === 1;
        });

        ray($filtered->count());

        $links = $filtered->reject(function (Imported $imported) {
            sleep(1);

            $response = Http::get(ImportBookHostels::getHostelworldURL($imported->city, $imported->name, $imported->intCode));

            $imported->new = ImportBookHostels::getHostelworldURL($imported->city, $imported->name, $imported->intCode);

            if (! $response->ok()) {
                ray($imported->toArray());
            }

            ray("ok - {$imported->id}");

            return $response->ok();

//            ray($response->ok(), ImportBookHostels::getHostelworldURL($imported->city, $imported->name, $imported->intCode));
        });

        ray($links);
    }

    //  php artisan hostelz:runTempFunction updateHwLinks
    private function updateHwLinks()
    {
        $items = Imported::select(['id', 'name', 'city', 'intCode', 'urlLink'])
            ->where([
                ['system', 'BookHostels'],
                ['status', 'active'],
            ])
//            ->limit(5000)
            ->get();

        ray($items->count());

        $items->each(function (Imported $imported) {
            tap($imported, function (Imported $imported) {
                $imported->urlLink = ImportBookHostels::getHostelworldURL($imported->city, $imported->name, $imported->intCode);
            })->save();
        });

        ray('end');
    }

    //  php artisan hostelz:cutPicsCountFromImported testWebSite
    private function cutPicsCountFromImported()
    {
        ray()->clearScreen();

        $all = Imported::where([
            'status' => Imported::STATUS_INACTIVE,
        ])
            ->has('picsObjects', '>', 3);

        ray($all->count())->label('all');

        Imported::where([
            'status' => Imported::STATUS_INACTIVE,
        ])
            ->has('picsObjects', '>', 3)
//            ->take(5000)
            ->lazyById()
//            ->each(fn(Imported $item) => ray([$item->id, $item->picsObjects->count()]))
            ->each(fn (Imported $item) => CutPicsCountImportedJob::dispatchSync($item));
//            ->each(fn(Imported $item) => ray([$item->refresh(), $item->id, $item->picsObjects->count()])) ;

        $all = Imported::where([
            'status' => Imported::STATUS_INACTIVE,
        ])
            ->has('picsObjects', '>', 3);

        ray($all->count())->label('all');
    }

    //  php artisan hostelz:runTempFunction removeOldPicsIfOnePicsType
    private function removeOldPicsIfOnePicsType()
    {
        return;

        // todo:
        ray()->clearScreen();

        $pics = Pic::query()
            ->where('lastUpdate', '<', now()->subYears(4)->format('Y-m-d'))
            ->whereIn('subjectType', ['imported'])
//            ->lazyById()
            ->take(10)
            ->get()
            ->filter(fn (Pic $pic) => count($pic->storageTypes) === 1)
            ->each(function (Pic $pic) {
                foreach ($pic->storageTypes as $sizeType => $storageType) {
                    if (! $pic->isExistsFileForSizeType($sizeType)) {
//                        ray(['picId' => $pic->id, 'listingId' => Imported::find($pic->subjectID)?->listing?->id]);
                        $pic->delete();
                    } else {
                        ray(['picId' => $pic->id, 'listingId' => Imported::find($pic->subjectID)?->listing?->id]);
                    }
                }
            });

        ray('end');
    }

    //  php artisan hostelz:runTempFunction testWebSite
    private function testWebSite()
    {
    }

    //  php artisan hostelz:runTempFunction devUpdate 10
    private function devUpdate($arguments = []): void
    {
        $count = (int) array_shift($arguments);

        if ($count === 0) {
            Artisan::call('hostelz:runTempFunction devUpdate 10');

            return;
        }

        $importeds = Imported::query()
            ->where('pics', 'LIKE', '["%')
            ->orderBy('hostelID')
            ->get();

        $total = $importeds->count();

        if ($total === 0) {
            return;
        }

        $chunkImporteds = $importeds
            ->take(10);

        $chunkImporteds->each(function (Imported $imported) {
            $newPicsUrls = collect($imported->pics)
                ->map(fn ($i) => str_replace(['[', ']', '"'], '', $i))
                ->map(fn ($i) => str_replace('\/', '/', $i))
                ->toArray();

            $imported->pics = $newPicsUrls;

            $imported->save();
        });

        $importedsBatchJobs = $chunkImporteds
            ->map(function (Imported $imp) {
                return new DevJob($imp->id);
            });

        Bus::batch($importedsBatchJobs)
            ->then(function (Batch $batch) use ($count) {
                sleep(10);

                Artisan::call('hostelz:runTempFunction devUpdate ' . ($count - 1));
            })
            ->name("Upload Images batch: {$count}; total Importeds: {$total}")
            ->onQueue('import')
            ->dispatch();
    }
}
