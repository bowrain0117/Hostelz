<?php

namespace App\Models\Listing;

use App\Helpers\EventLog;
use App\Jobs\Imported\DownloadPicsImportedJob;
use App\Models\AttachedText;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Imported;
use App\Models\Languages;
use App\Models\Rating;
use App\Models\User;
use App\Services\ImportSystems\ImportSystems;
use App\Services\WebsiteStatusChecker;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lib\DataCorrection;
use Lib\Emailer;
use Lib\Geocoding;
use Lib\Spider;

class ListingMaintenance
{
    public Listing $listing;

    protected string $output = '';

    public const SNIPPET_LENGTH = 350; // must be longer than the snippet length used in _listingsList-list.blade.php.

    public const SNIPPET_ELLIPSIS = ' …';

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    public function __call($methodName, $args)
    {
        return call_user_func_array([$this->listing, $methodName], $args);
    }

    public function __set($name, $value): void
    {
        $this->listing->$name = $value;
    }

    public function __get($name)
    {
        return $this->listing->$name;
    }

    /* Static Methods */

    // Create a new instance of this class (provided for convenience)
    public static function create(Listing $listing): self
    {
        return new static($listing);
    }

    public static function maintenanceTasks($timePeriod): string
    {
        $output = '';

        switch ($timePeriod) {
            case 'hourly':

                // * Listing Updates *

                // (We do these hourly instead of daily because if we try to do too many at a time the script runs out of memory.)
                // Average time is 12s/listing.  So max ~300/hour.

                // Note: It's ok if this runs more than an hour, we use "withoutOverlapping()" so it will only one run of these jobs at a time.
                set_time_limit(2 * 60 * 60);

                // Verified, hostel

                $limitLiveListingsHourlyMaintenanceTasks = config('custom.limitLiveListingsHourlyMaintenanceTasks');

                //  live first
                $msg = 'live';
                $verifiedHostels = Listing::areLive()
                    ->arePrimaryPropertyType()
                    ->where(function ($query): void {
                        $query->whereNull('lastUpdate')
                            ->orWhere('lastUpdate', '<', Carbon::now()->subDays(30)->format('Y-m-d'));
                    })
                    ->orderBy('lastUpdate')
                    ->limit($limitLiveListingsHourlyMaintenanceTasks)
                    ->get();
                if ($verifiedHostels->isEmpty()) {
                    $msg = 'not live';
                    $verifiedHostels = Listing::where('verified', '>=', Listing::$statusOptions['ok'])
                        ->arePrimaryPropertyType()
                        ->where(function ($query): void {
                            $query->whereNull('lastUpdate')
                                ->orWhere('lastUpdate', '<', Carbon::now()->subDays(30)->format('Y-m-d'));
                        })
                        ->orderBy('lastUpdate')
                        ->limit(30)
                        ->get();
                }

                $output .= "Listing Updates ({$msg} | verified, hostel): " . self::updateListings($verifiedHostels) . "\n";

                $verifiedItems = Listing::areLive()
                    ->areNotPrimaryPropertyType()
                    ->where(function ($query): void {
                        $query->whereNull('lastUpdate')
                            ->orWhere('lastUpdate', '<', Carbon::now()->subDays(20)->format('Y-m-d'));
                    })
                    ->orderBy('lastUpdate')->limit(20)->get();
                if ($verifiedItems->isEmpty()) {
                    $verifiedItems = Listing::where('verified', '>=', Listing::$statusOptions['ok'])
                        ->arePrimaryPropertyType()
                        ->where(function ($query): void {
                            $query->whereNull('lastUpdate')
                                ->orWhere('lastUpdate', '<', Carbon::now()->subDays(30)->format('Y-m-d'));
                        })
                        ->orderBy('lastUpdate')->limit(20)->get();
                }

                // Verified, Non-hostel
                $output .= 'Listing Updates (verified, non-hostel): ' . self::updateListings($verifiedItems) . "\n";

                // Non-Verified (checks these for things like duplicates, availability/check prices to see if a special/removed one should be approved, etc.)
                $output .= 'Listing Updates (unverified): ' . self::updateListings(
                    Listing::where('verified', '>=', Listing::$statusOptions['newIgnored'])
                        ->where('verified', '<', Listing::$statusOptions['ok'])
                        ->where(function ($query): void {
                            $query->whereNull('lastUpdate')
                                ->orWhere('lastUpdate', '<', Carbon::now()->subDays(90)->format('Y-m-d'));
                        })
                        ->orderBy('lastUpdate')->limit(20)->get()
                ) . "\n";

                break;

            case 'daily':

                set_time_limit(2 * 60 * 60);

                $output .= 'Update locationStatus of Listings: ';

                $okCount = $outliersCount = 0;

                foreach (CityInfo::areLive()->cursor() as $cityInfo) {
                    $listings = Listing::areLive()->byCityInfo($cityInfo)->haveLatitudeAndLongitude()->get();
                    if ($listings->isEmpty()) {
                        continue;
                    }
                    if ($listings->count() === 1) {
                        // Only one listing... we assume it must be ok...
                        if ($listings->first()->locationStatus !== 'ok') {
                            $listings->first()->locationStatus = 'ok';
                            $listings->first()->save();
                            $okCount++;
                        }

                        continue;
                    }

                    // Group listings into clusters within 100 KM of each other.

                    $clusters = [];
                    foreach ($listings as $listing) {
                        foreach ($clusters as $key => $cluster) {
                            if ($cluster['centerPoint']->distanceToPoint($listing->geoPoint(), 'km') < 100) {
                                // found our cluster
                                $clusters[$key]['listings'][] = $listing;

                                continue 2;
                            }
                        }
                        // no clusters found in this area, create a new cluster
                        $clusters[] = ['centerPoint' => $listing->geoPoint(), 'listings' => [$listing]];
                    }

                    // Find largest cluster

                    $largestClusterCount = $largestClusterKey = 0;
                    foreach ($clusters as $clusterKey => $cluster) {
                        $listingCount = count($cluster['listings']);
                        if ($listingCount > 1 && $listingCount > $largestClusterCount) {
                            $largestClusterCount = $listingCount;
                            $largestClusterKey = $clusterKey;
                        }
                    }

                    // Set the locationStatus values

                    foreach ($clusters as $clusterKey => $cluster) {
                        $listingsInCluster = count($cluster['listings']);
                        if ($clusterKey === $largestClusterKey || // This is the largest cluster
                            $largestClusterCount <= 2 || // Or the largest cluster is insignificant anyway
                            $listingsInCluster >= 3) { // Or *any* cluster of at least a few listings is considered ok
                            // Mark these listings as 'ok'
                            foreach ($cluster['listings'] as $listing) {
                                if ($listing->locationStatus === 'ok') {
                                    continue;
                                }
                                $listing->locationStatus = 'ok';
                                $listing->save();
                                $okCount++;
                            }
                        } else {
                            // Mark these listings as 'outlier'
                            foreach ($cluster['listings'] as $listing) {
                                if ($listing->locationStatus === 'outlier') {
                                    continue;
                                }
                                $listing->locationStatus = 'outlier';
                                $listing->save();
                                $okCount++;
                            }
                        }
                    }
                }

                // to free the memory
                unset($clusters, $listings);

                $output .= "Changes: $okCount ok, $outliersCount outliers\n\n";

                $output .= 'Checking for scraping issues.';
                // (Only if there were more than a few recent listing updates done.)
                if (Listing::where('lastUpdate', '=', Carbon::now()->subDays(1)->format('Y-m-d'))->count() > 5) {
                    // Ratings imports
                    if (time() - Cache::get('ImportSystems:mostRecentSuccessfulRatingImports:Hostelbookers') > 24 * 60 * 60) {
                        logError('No successful Hostelbookers rating imports.  Possible scraping problem.');
                    }
                    if (time() - Cache::get('ImportSystems:mostRecentSuccessfulRatingImports:Hostels_com') > 24 * 60 * 60) {
                        logError('No successful Hostels.com rating imports.  Possible scraping problem.');
                    }
                    if (time() - Cache::get('ImportSystems:mostRecentSuccessfulRatingImports:Hostelworld') > 24 * 60 * 60) {
                        logError('No successful Hostelworld rating imports.  Possible scraping problem.');
                    }

                    // Pics imports
                    if (time() - Cache::get('ImportSystems:mostRecentSuccessfulPicsImports:Hostelworld') > 24 * 60 * 60) {
                        logError('No successful Hostelworld *pics* imports.  Possible scraping problem.');
                    }
                }

                break;

            case 'weekly':
                $output .= 'Setting onlineReservations.';
                DB::statement('UPDATE listings SET onlineReservations=0 WHERE onlineReservations=1 AND ' .
                    'id NOT IN (SELECT hostelID FROM imported WHERE status="active" AND availability=1)');
                DB::statement('UPDATE listings SET onlineReservations=1 WHERE onlineReservations=0 AND ' .
                    'id IN (SELECT hostelID FROM imported WHERE status="active" AND availability=1)');

                $output .= self::makeLocationDataCorection($output);

                $output .= self::setContinent($output);

                break;

            case 'monthly': // note: These should only be monthly tasks that don't make sense to just put in the 'afterListingDataImport' section.

                set_time_limit(3 * 60 * 60);

                $output .= 'Remove imported listings that were never made live and are now inactive: ';
                $listings = Listing::where('verified', Listing::$statusOptions['imported'])->
                whereHas('importeds', function ($query): void {
                    $query->where('status', 'inactive');
                })->whereDoesntHave('importeds', function ($query): void {
                    $query->where('status', 'active');
                })->doesntHave('reviews')->get();
                foreach ($listings as $listing) {
                    $output .= $listing->id . ' ';
                    $listing->delete();
                }
                $output .= "\n";

                $output .= "\nOptimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . Listing::$staticTable);

                break;

            case 'afterListingDataImport':
                $output .= Listing::maintenanceTasks('weekly'); // first do all of the weekly tasks!
                // (do other stuff here)
                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function updateListings(Collection $listings, $quickMode = false, $allowAutoMerging = true): string
    {
        $output = '';
        foreach ($listings as $listing) {
            $output .= "$listing->id ";

            $listingMaintenance = new self($listing);
            if (! $listingMaintenance->listing) {
                $output .= '(not found -- was merged?) ';

                continue;
            }
            $output .= $listingMaintenance->updateListing($quickMode, $allowAutoMerging);
        }

        return $output;
    }

    /* Misc */

    private function doQuickFixes(
        $value,
        $substringReplacements,
        $wholeWordReplacements = null,
        $exactStringReplacements = null,
        $trimStrings = null,
        $trimIfBothStartAndEnd = null
    ) {
        $originalValue = $value;
        if ($substringReplacements) {
            $value = str_replace(array_keys($substringReplacements), array_values($substringReplacements), $value);
        }
        if ($wholeWordReplacements) {
            foreach ($wholeWordReplacements as $from => $to) {
                $value = wholeWordStringReplace($from, $to, $value);
            }
        }
        if ($exactStringReplacements) {
            foreach ($exactStringReplacements as $from => $to) {
                if (! strcasecmp($value, $from)) {
                    $value = $to;
                }
            }
        }
        if ($trimStrings) {
            $value = trimUsingArrayOfStrings($value, $trimStrings);
        }
        if ($trimIfBothStartAndEnd) {
            foreach ($trimIfBothStartAndEnd as $trim) {
                if (! Str::startsWith($value, $trim) || ! Str::endsWith($value, $trim)) {
                    continue;
                }
                $value = trimUsingArrayOfStrings($value, [$trim]);
            }
        }
        // If a change was made, run it again to find any nested replacements that need to be done the next time around
        if ($value !== $originalValue) {
            return $this->doQuickFixes(
                $value,
                $substringReplacements,
                $wholeWordReplacements,
                $exactStringReplacements,
                $trimStrings,
                $trimIfBothStartAndEnd
            );
        }

        return $value;
    }

    public function quickFixesBeforeSaving(): void
    {
        // Multiple fields
        foreach (['name', 'city', 'cityAlt', 'address', 'zipcode', 'tel', 'fax'] as $field) {
            $this->$field = $this->doQuickFixes(
                $this->$field,
                ['  ' => ' ', ' .' => '.', ' ,' => ',', '( ' => '(', ' )' => ')', ',.' => '.', ',,' => ',',
                    '’' => '\'', '´' => '\'', '`' => '\'', '“' => '"', '”' => '"', '„' => '"', '' => '"', '' => '"',
                    '&nbsp;' => ' ', ],
                null,
                ['no' => '', 'none' => '', 'n/a' => '', 'not applicable' => '', 'no fax' => '', 'no phone' => ''],
                [' ', "\t", "\n", "\r", "\0", "\x0B", '_', '-', '/'],
                ['"', "'"] // trim if both starts and ends with
            );
        }

        // Name
        $this->name = $this->doQuickFixes(
            $this->name,
            null,
            ['and' => '&', 'BandB' => 'B&B'],
            null,
            [' 2*', ' 3*', ' 4*', '*']
        );

        // Web
        $this->web = $this->doQuickFixes(
            $this->web,
            ['http://:' => 'http://', 'http:///' => 'http://'],
            null,
            ['None' => '', 'na' => '', 'Null' => '']
        );
        if ($this->web === '') {
            $this->webStatus = WebsiteStatusChecker::$websiteStatusOptions['unknown'];
        }

        // Postal Code
        $this->zipcode = $this->doQuickFixes(
            $this->zipcode,
            null,
            null,
            ['none' => ''],
            ['.'],
            null
        );

        // Email Fields

        foreach (Listing::$emailFields as $emailField) {
            $emails = $this->$emailField;
            if (! $emails) {
                continue;
            }
            // Do email fixes
            $emails = array_unique(array_map('mb_strtolower', $emails));
            $this->$emailField = $emails;
        }
    }

    public function updateListing($quickMode = false, $allowAutoMerging = true): string
    {
        if ($this->listing->city === '' || $this->listing->country === '') {
            // (Shouldn't happen, but if it does the missing city causes issues for findDuplicates() and possibly other code.)
            logError("$this->listing->id missing city or country.");

            return '';
        }

        $this->output = '';

        $this->elapsedTime();

        $this->updateActiveImportedStatus();

        // ** Video **

        /*
        $output .= '('.$this->elapsedTime().'s) Upload video to YouTube: ';
        $output .= $this->uploadVideoToYoutube()."\n";
        */

        $this->addressFix(true);

        // ** Find Duplicate Listings **

        // (Note: We do this first because if it merges with another listing, this listing may no longer even exist after this function returns.)
        $didAutoMerge = $this->findDuplicatedListings($allowAutoMerging);
        if ($didAutoMerge) {
            return trim($this->output);
        } // our values probably changed, or our listing may not even exist any more, so abort the listing update.

        if ($this->listing->verified <= Listing::$statusOptions['unlisted']) {
            logError("updateListing attempted on a listing that shouldn't be updated (listing {$this->listing->id}).");
            $this->output .= '!!! removed/closed listings cannot be updated !!!';

            return $this->output;
        }

        // Also update the priorityLevels of listing duplicates related to this listing (in case the listing statuses changed)
        $this->updatePriorityLevel();

        // ** Update Imported Descriptions/Ratings **

        // (do this before pics stuff because it might also update the pic URLs)
        if ($this->listing->isLive() && ! $this->listing->activeImporteds->isEmpty()) {
            $this->updateActiveImporteds();

            $this->output .= "\n(" . $this->elapsedTime() . 's) Download imported pics: ';
            if ($quickMode) {
                $this->output .= 'Skipping Download imported pics (quick mode)';
            } else {
                $this->downloadImportedPics();
            }

            $this->output .= "\n";
        }

        $this->listing->refresh();

        // ** OnlineBooking **

        // (this is also set by maintenanceTasks() for all listings at once, but we also do it here just in case since it effects isLive())
        $this->output .= '(' . $this->elapsedTime() . 's) Set online reservations status: ';
        // (this needs to be done before the combinedRating is calculated)
        $this->onlineReservations = $this->listing->hasImportSystemWithOnlineBooking();

        $this->listing->preferredBooking = $this->listing->isPreferredBooking();

        $this->output .= ($this->onlineReservations ? 'true' : 'false') . ($this->preferredBooking ? " (is in preferred systems)\n" : " (not in preferred systems)\n");

        // ** Check & Spider Website **

        if (! $quickMode) {
            $this->updateWebsiteInfo($quickMode);
        }

        // ** PropertyType **

        $this->updatePropertyType();

        // ** Features **

        $this->compileFeatures();

        // ** Update Owner Texts Originality Scores **

        $this->output .= '(' . $this->elapsedTime() . 's) Update originality scores of owner text: ' . $this->updateOriginalityScores() . "\n";

        // ** Content Scores **

        $this->output .= '(' . $this->elapsedTime() . 's) Content scores: ' . $this->calculateContentScores() . "\n";

        // ** Update Combined Rating **

        $this->output .= '(' . $this->elapsedTime() . 's) ' . $this->calculateCombinedRating() . "\n";

        // ** Geocoding **

        if (! $quickMode) {
            $this->setBestGeocoding(); // sets $this->latitude and $this->longitude
        }

        // ** Email Mgmt (if needed) **

        $this->output .= 'Email Mgmt (if needed): ' . $this->sendListingMgmtEmails() . "\n";

        // ** Write Results **

        $this->listing->lastUpdate = date('Y-m-d');
        $this->listing->save();

        if ($this->listing->isLive()) {
            // ** Update Snippets **
            $this->output .= '(' . $this->elapsedTime() . 's) Update snippets: ' . implode(', ', array_keys($this->updateSnippets())) . "\n";
        }

        $this->listing->clearRelatedPageCaches();

        $this->output .= '(' . $this->elapsedTime() . 's) Done.';

        EventLog::log(
            'staff',
            'updateListing',
            'Listing', $this->id,
            'type: ' . $this->propertyType . ' / name: ' . $this->name
        );

        return $this->output;
    }

    public function sendListingMgmtEmails()
    {
        if ($this->contactStutus === Listing::$contactStatusOptions['dontContact']) {
            return "Don't contact.";
        }

        if (! $this->isLive()) {
            return 'Not live.';
        }

        if (! $this->isPrimaryPropertyType()) {
            return 'Not a hostel.';
        }

        $hasManager = User::areMgmtForListing($this->id)->exists();

        if ($hasManager) {
            // For now we don't email them again
            return 'Already has management users.';
        }

        /* (never mind, it's probably ok to just send English emails to everyone)
        $language = $this->determineLocalLanguage();
        if ($language != 'en') {
            // For now we're just doing English countries.
            return "Not English, skipping for now.";
        }
        */
        $language = 'en';

        $emailAddresses = $this->getAllEmails(['supportEmail', 'managerEmail', 'bookingsEmail']);

        $output = '';

        foreach ($emailAddresses as $emailAddress) {
            $output .= "$emailAddress ";

            if ($emailAddress === '' ||
                stripos($emailAddress, 'hostelworld') !== false ||
                stripos($emailAddress, 'webresint') !== false ||
                stripos($emailAddress, '@gomio.com') !== false ||
                stripos($emailAddress, 'jissen-inb.com') !== false /* hostelworld's japan rep -- do not email */) {
                $output .= 'Unacceptable email. ';

                continue;
            }

            $lastEmailedEvent = EventLog::where('subjectType', 'Listing')->where('subjectID', $this->id)
                ->where('action', 'mass email')->where('data', $emailAddress)->orderBy('eventTime', 'DESC')->first();

            if ($lastEmailedEvent) {
                // For now don't email them again.
                $output .= 'Already emailed. ';

                continue;
            }

            //$listingIsIncomplete = ($listing['mgmtFeatures'] == '' || strpos($listing['mgmtFeatures'], 'allNonsmoking') === false ||
            //    !dbGetOne("SELECT id FROM attached WHERE subjectID=$listing[id] AND type='description' AND source='owner' AND subjectType='hostels'"));

            $emailType = 'maybeInitialEmail';

            $emailText = str_replace('<br>', "<br>\n", langGet(
                "emailOwners.$emailType",
                ['listingName' => $this->name, 'managementLink' => User::mgmtSignupURL($this->id, $language)],
                [],
                $language
            ));

            Emailer::send($emailAddress, $this->name, 'generic-email', ['text' => $emailText], config('custom.listingSupportEmail'));
            EventLog::log('system', 'mass email', 'Listing', $this->id, $emailType, $emailAddress);
            $output .= 'emailing now.';

            // We just send to one email address at a time, so we stop after sending one.
            break;
        }

        return $output;
    }

    public function uploadVideoToYoutube($forceReUpload = false)
    {
        // Set up

        $videoTempPath = config('custom.userRoot') . '/data/video-temp/';
        // the dash after is so when we do a wildcard (*) glob() to find the file, we only get the one for this listing
        $sourceFileRoot = $videoTempPath . 'source-video-' . $this->id . '-';
        $finalVideo = $videoTempPath . 'output-' . $this->id . '.mkv';
        $ourVideoClipsPath = config('custom.userRoot') . '/videos/';

        // Delete any old files in the temp dirs

        $files = glob($videoTempPath . '*');
        $now = time();
        foreach ($files as $file) {
            if (is_dir($file) || $now - filemtime($file) < 60 * 60 * 24) {
                continue;
            }
            logError("Old file $file found in video temp dir. Deleting.");
            unlink($file);
        }

        // See if we should upload this listing's video

        if ($this->videoURL === '') {
            return 'no video';
        }
        if (! $this->isLive()) {
            return 'Listing not live.';
        }
        if ($this->propertyType !== 'Hostel') {
            return 'Not a hostel.';
        }
        if (! $this->cityInfo) {
            return 'No cityInfo.';
        }

        // See if we already uploaded this listing's video (for any listing)

        if (! $forceReUpload) {
            $previousUploadingOfSameVideo = EventLog::where('subjectType', 'Listing')
                ->where('action', 'uploadVideo')->where('subjectString', $this->videoURL)
                ->orderBy('id', 'desc')->first();
            if ($previousUploadingOfSameVideo) {
                $previousUploadingVideoID = $previousUploadingOfSameVideo->data;
                if (! strpos($this->videoEmbedHTML, $previousUploadingVideoID)) {
                    // Either the videoEmbedHTML wasn't set at all, or was for a different video,
                    // or it was the temporary videoEmbedHTML for the video that wasn't ours. So we fix it.
                    $this->setVideoEmbedHtml($previousUploadingVideoID);
                }

                return 'Already done.';
            }
        }

        // Download Video
        //  https://github.com/ytdl-org/youtube-dl

        unset($output);
        $command = 'youtube-dl 2>&1 ' .
            '-f ' . escapeshellarg('bestvideo[height<=?1080]+bestaudio/best') . ' ' . // we don't really need 1920x resolution.
            '--cache-dir ' . escapeshellarg($videoTempPath . 'youtube-dl-cache') . ' ' .
            '--abort-on-unavailable-fragment --no-mtime --no-playlist ' .
            '--force-ipv4 ' .
            '-o ' . escapeshellarg($sourceFileRoot) . ' ' .
            escapeshellarg($this->videoURL);
        $lastOutputLine = exec($command, $output, $returnCode);
        // echo "\n\nReturn Code: $returnCode. Output: " . json_encode($output) . "\n\n";

        $files = glob($sourceFileRoot . '*');
        if (! $files) {
            $this->videoEmbedHTML = '';
            $videoURL = $this->videoURL;
            $outputAsString = json_encode($output);

            // Some failure messages are worth issuing an error,
            // but if the video was just missing or whatever, we just remove it.
            $unimportantMessages = ['This video does not exist.'];
            $hasUnimportantMessage = collect($unimportantMessages)->first(function ($unimportantMessage, $key) use ($outputAsString) {
                return Str::contains($outputAsString, $unimportantMessage);
            });
            if ($hasUnimportantMessage) {
                logWarning("Download failed for $this->id video at $this->videoURL for unimportant reason. Return Code: $returnCode. Output: $outputAsString");
                $this->videoURL = '';
            } else {
                logError("Download failed for $this->id video at $this->videoURL. Return Code: $returnCode. Output: $outputAsString");
            }

            return "Download failed. Return Code: $returnCode at $videoURL. Output: $outputAsString";
        }
        $sourceFile = $files[0];

        // Get Video Info

        unset($output);
        $lastOutputLine = exec(
            'ffprobe ' .
            // The "-i" arguments are the inputs, which are numbered from 0.
            '-select_streams v:0 -show_entries stream -print_format json ' .
            escapeshellarg($sourceFile),
            $output,
            $returnCode
        );
        // echo "\n\nReturn Code: $returnCode. Output: " . json_encode($output) . "\n\n";

        if ($returnCode) {
            logError("ffprobe failed for $this->id video. Return Code: $returnCode. Output: " . json_encode($output));

            return "ffprobe failed. Return Code: $returnCode. Output: " . json_encode($output);
        }

        $output = json_decode(implode("\n", $output));
        $sourceWidth = intval($output->streams[0]->width);
        $sourceHeight = intval($output->streams[0]->height);
        $sourceSampleAspectRatio = ! empty($output->streams[0]->sample_aspect_ratio)
            ? (string) $output->streams[0]->sample_aspect_ratio
            : '';

        if (! $sourceWidth || ! $sourceHeight || $sourceSampleAspectRatio === '') {
            logError("ffprobe invalid for $this->id video. Return Code: $returnCode. Output: " . json_encode($output));

            return "ffprobe invalid. Return Code: $returnCode. Output: " . json_encode($output);
        }

        // Convert Video

        $sourceSampleAspectRatio = str_replace(':', '/', $sourceSampleAspectRatio);

        unset($output);
        $command = 'nice -n 20 cpulimit -l 50 -- ffmpeg 2>&1 -y ' .
            // The "-i" arguments are the inputs, which are numbered from 0.
            '-i ' . escapeshellarg($ourVideoClipsPath . 'logo-video.mp4') . ' ' .
            '-i ' . escapeshellarg($sourceFile) . ' ' .
            '-i ' . escapeshellarg(public_path() . '/images/logo-video-overlay.png') . ' ' .
            '-i ' . escapeshellarg($ourVideoClipsPath . 'promo-video.mp4') . ' ' .
            // The "-filter_complex" options specify the input, then the filters, then gives them an output name.
            '-filter_complex "' .
            "[0:v] scale=$sourceWidth:$sourceHeight, setsar=sar=$sourceSampleAspectRatio [scaledLogoVideo]; " . // make the logo video the same resolution as the source video
            "[3:v] scale=$sourceWidth:$sourceHeight, setsar=sar=$sourceSampleAspectRatio [scaledPromoVideo]; " . // make the promo video the same resolution as the source video
            '[2:v] format=argb,colorchannelmixer=aa=0.3 [ourOverlay]; ' . // get the watermark image and reduce its opacity to 0.4
            '[1:v][ourOverlay] overlay=main_w-overlay_w-10:main_h-overlay_h-50 [sourceWithOverlay]; ' . // overlay watermark over the main video
            '[scaledLogoVideo] [0:a:0] [sourceWithOverlay] [1:a:0] [scaledPromoVideo] [3:a:0] concat=n=3:v=1:a=1 [finalVideo] [finalAudio]" ' . // concatanate all segments
            '-map "[finalVideo]" -map "[finalAudio]" ' . // choose final output streams
            '-codec:v libx264 -codec:a libvorbis ' . // choose output encodings
            escapeshellarg($finalVideo); // output file
        $lastOutputLine = exec($command, $output, $returnCode);
        //echo "\n\nReturn Code: $returnCode. Output: " . json_encode($output) . "\n\n";

        unlink($sourceFile);

        if ($returnCode || ! file_exists($finalVideo)) {
            logError("Conversion failed for $this->id video. Return Code: $returnCode. Output: " . json_encode($output));

            return "Conversion failed. Return Code: $returnCode. Output: " . json_encode($output);
        }

        // Upload Video

        // (We have to convert it to ASCII because the youtube-upload script was getting errors with UTF-8 characters.)
        $description = 'See ' . $this->getURL('publicSite', 'en', true, true) . ' for reviews and price comparison for ' . utf8ToAscii($this->name) . '. ' .
            'Or visit ' . $this->cityInfo->getURL('publicSite') . ' for a complete list of all ' . utf8ToAscii($this->city) . ' hostels.';

        //  https://github.com/tokland/youtube-upload
        $command = 'youtube-upload 2>&1 ' .
//            '--client-secrets='.escapeshellarg(config('custom.userRoot').'/videos/hostelz-videos-youtube-oauth.json') . ' ' .
//            '--credentials-file='.escapeshellarg(config('custom.userRoot').'/videos/hostelz-videos-youtube-credentials') . ' ' .
            '--client-secrets=' . escapeshellarg(config('custom.userRoot') . '/videos/secret.json') . ' ' .
            '--credentials-file=' . escapeshellarg(config('custom.userRoot') . '/videos/youtube-credentials') . ' ' .
            '--privacy public ' .
            '--default-language="en" --default-audio-language="en" ' .
            '--category="Travel & Events" --tags="hostel, travel" ' .
            '--description=' . escapeshellarg($description) . ' ' .
            '--title=' . escapeshellarg(utf8ToAscii($this->name . ' - ' . $this->city . ', ' . $this->country)) . ' ' .
            escapeshellarg($finalVideo);
        unset($output);
        $youtubeVideoID = exec($command, $output, $returnCode);
        // echo "\n\nReturn Code: $returnCode. Output: " . json_encode($output) . "\n\n";

        unlink($finalVideo);

        if ($returnCode || $youtubeVideoID === '') {
            logError("Upload failed for $this->id video. Return Code: $returnCode. Output: " . json_encode($output));

            return "Upload failed. Return Code: $returnCode. Output: " . json_encode($output);
        }

        $this->setVideoEmbedHtml($youtubeVideoID);

        // (This log format must remain the same because we use the log to retrieve info about past video uploads.)
        EventLog::log('system', 'uploadVideo', 'Listing', $this->id, $this->videoURL, $youtubeVideoID);

        return 'Upload complete. ' . $youtubeVideoID;
    }

    private function setVideoEmbedHtml($youtubeVideoID): void
    {
        $this->videoEmbedHTML = '<iframe type="text/html" width="620" height="349" ' . // not perfect aspect ratio for all videos, but works ok for most
            'src="https://www.youtube.com/embed/' . $youtubeVideoID . '" scrolling="no" frameborder="0" allowfullscreen></iframe>';
    }

    public function calculateContentScores()
    {
        $output = '';

        // The a $contentScore[] element is set for each language.
        // The content score is roughly based on an estimate of the number of unique characters on the listing page for that language.
        $contentScores = [];
        foreach (Languages::allLiveSiteCodes() as $language) {
            $reviewScore = ($this->getLiveReview($language) ? strlen($this->getLiveReview($language)->editedReview) : 0);

            /*
            $description = $this->getText('description', $language);
            if ($description && $this->isTextOriginalEnough($description)) {
                $descriptionScore = round(($description->score / 100) * strlen($description->data));
            } else {
                $descriptionScore = 0;
            }
            */
            // No longer counting descriptions as content.  There's a good chance it's duplicated somewhere.
            $descriptionScore = 0;

            $ratings = Rating::spliceRatingsForPage(Rating::getRatingsForListing($this, $language), 0);
            $ratingsScore = 0;
            foreach ($ratings as $rating) {
                $ratingsScore += strlen($rating->summary) + strlen($rating->comment);
            }

            $contentScore = $reviewScore + $descriptionScore + $ratingsScore;

            if ($contentScore) {
                $contentScores[$language] = $contentScore;
                $output .= "$language: reviewScore:$reviewScore + descriptionScore:$descriptionScore + ratingsScore:$ratingsScore = contentScore:$contentScore. ";
            }
        }

        $this->contentScores = $contentScores;
        $this->overallContentScore = $contentScores['en'] ?? null;

        return $output;
    }

    public function spiderWebsite()
    {
        $spider = new Spider();
        $spider->maxTotalPages = 15;
        $spider->maxLinksToFollowPerPage = 10;
        $spider->maxPageDataRead = 50000;

        $linkPatterns = [
            /* Old ones, replaced with the ones below (note some of the old ones had different capitalization for the array keys)
            'Hostelsclub'=>'`https?\:\/\/(^|.+\.)hostelsclub\.com(.+?)$`i',
            'Hostelbookers'=>'`https?\:\/\/(^|.+\.)hostelbookers\.com(.+?)$`i',
            'hb-247.com'=>'`https?\:\/\/(^|.+\.)hb-247\.com(.+?)$`i', // hostelbookers affiliate site
            'BookHostels'=>'`https?\:\/\/(^|.+\.)bookhostels\.com(.+?)$`i', // hostelworld affiliate site
            */
            'Hostelz' => '`https?\:\/\/(^|.+\.)hostelz\.com(.+?)$`i',
            'Hostelworld' => '`https?\:\/\/(^|.+\.)hostelworld\.com(.+?)$`i',
            'Hostelworld Affiliate' => '`https?\:\/\/(^|.+\.)(bookhostels\.com|hostelworld\.com(.+?)\?affiliate=)(.+?)$`i',
            'HostelBookers' => '`https?\:\/\/(^|.+\.)hostelbookers\.com(.+?)$`i',
            'HostelBookers Affiliate' => '`https?\:\/\/(^|.+\.)(hb-247\.com|hostelbookers\.com(.+?)\?affiliate=)(.+?)$`i',
            'Hostels.com' => '`https?\:\/\/(^|.+\.)hostels\.com(.+?)$`i',
            'HostelsClub' => '`https?\:\/\/(^|.+\.)hostelsclub\.com(.+?)$`i',
            // Possible contacts:
            'Facebook' => '`https?\:\/\/(^|.+\.)facebook\.com(.+?)$`i',
            'Twitter' => '`https?\:\/\/(^|.+\.)twitter\.com(.+?)$`i',
            'LinkedIn' => '`https?\:\/\/(^|.+\.)linkedin\.com(.+?)$`i',
            // Videos:
            'YouTube' => '`https?\:\/\/(^|.+\.)youtube\.com(.+?)$`i',
            'Vimeo' => '`https?\:\/\/(^|.+\.)vimeo\.com(.+?)$`i',
            'Metacafe' => '`https?\:\/\/(^|.+\.)metacafe\.com(.+?)$`i',
            'Viddler' => '`https?\:\/\/(^|.+\.)vidler\.com(.+?)$`i',
        ];

        return $spider->spiderSiteWithCaching($this->web, 2, $linkPatterns, 'domain', 'listing', true, 365);
    }

    public function calculateCombinedRating(): string
    {
        $output = "Combined Rating: Original: $this->combinedRating. ";

        $combinedRatingCount = 0;

        // Array of [ 'averageRating', 'count', 'weight', 'name' (just used for our output) ]
        $ratings = [];

        $maxCountOfImportedSystem = 30; // limit the weight of any one system
        foreach ($this->importeds as $imported) {
            if (! $imported->rating || $imported->status !== 'active') {
                continue;
            }
            $weight = ($imported->status === 'active' ? 1.0 : 0.5);
            if ($imported->getImportSystem()->multipleRatingSites) {
                // For BookHostels... multiple ratings (Hostels.com and Hostelworld)
                foreach ($imported->rating as $siteName => $rating) {
                    if (! $rating || ! $rating['count']) {
                        continue;
                    }
                    $ratings[] = ['averageRating' => intval($rating['overall']), 'count' => min($rating['count'], $maxCountOfImportedSystem), 'weight' => $weight, 'name' => $siteName];
                    $combinedRatingCount += $rating['count'];
                }
            } else {
                if (! isset($imported->rating['count'])) {
                    throw new Exception("Invalid rating data for imported $imported->id.");
                }
                if (! $imported->rating['count']) {
                    continue;
                }
                if (! $imported->rating['overall']) {
                    throw new Exception("Zero overall rating for $imported->id (shouldn't happen).");
                }
                $ratings[] = ['averageRating' => (int) $imported->rating['overall'], 'count' => min($imported->rating['count'], $maxCountOfImportedSystem),
                    'weight' => $weight, 'name' => $imported->getImportSystem()->shortName(), ];
                $combinedRatingCount += $imported->rating['count'];
            }
        }

        // Consider Our Ratings

        $ourRatings = Rating::getRatingsForListing($this)->filter(function ($rating) {
            return $rating->rating !== 0;
        });
        $combinedRatingCount += $ourRatings->count();
        $consideredToBeRecent = Carbon::now()->subYear(4);

        $recentRatings = $ourRatings->filter(function ($rating) use ($consideredToBeRecent) {
            return $rating->commentDate > $consideredToBeRecent;
        });
        if (! $recentRatings->isEmpty()) {
            $ratingsSum = $recentRatings->reduce(function ($carry, $rating) {
                return $carry + $rating->asAPercent();
            });
            $ratings[] = [
                'averageRating' => round($ratingsSum / $recentRatings->count()),
                'count' => $recentRatings->count(), 'weight' => 2.0,
                'name' => 'Hostelz Recent Ratings',
            ];
        }

        if ($recentRatings->count() < 6) { // If there aren't a lot of new ratings, use the old ones...
            $notRecentRatings = $ourRatings->filter(function ($rating) use ($consideredToBeRecent) {
                return $rating->commentDate <= $consideredToBeRecent;
            });
            if (! $notRecentRatings->isEmpty()) {
                $ratingsSum = $notRecentRatings->reduce(function ($carry, $rating) {
                    return $carry + $rating->asAPercent();
                });
                $ratings[] = [
                    'averageRating' => round($ratingsSum / $notRecentRatings->count()),
                    'count' => $notRecentRatings->count(), 'weight' => 0.25,
                    'name' => 'Hostelz Old Ratings', ];
            }
        }

        // Paid Review rating
        // (doesn't currently check for reviews in multiple languages)
        $review = $this->getLiveReview('');
        if ($review && $review->rating) {
            $ratings[] = ['averageRating' => $review->ratingAsAPercent(), 'count' => 1, 'weight' => 4.0, 'name' => 'Paid Review'];
            $combinedRatingCount += 4; // our paid review counts as several
        }

        // Calculate Combined Rating

        $scoreTotal = $weightedCountTotal = 0;
        foreach ($ratings as $rating) {
            $output .= "$rating[name]: $rating[averageRating]% ($rating[count] count, $rating[weight] weight) ";
            $scoreTotal += $rating['averageRating'] * $rating['count'] * $rating['weight'];
            $weightedCountTotal += $rating['count'] * $rating['weight'];
        }

        if ($weightedCountTotal === 0) {
            $this->combinedRating = 0;
            $this->combinedRatingCount = 0;
        } else {
            $DAMPER_COUNT = 3; // adds in this many "damper" reviews to tend the score towards a percent if there are fewer reviews
            // For listings without many reviews, we default to a slightly higher default if they have onlineReservations and preferredBooking.
            if ($this->onlineReservations) {
                $TEND_TOWARDS_PERCENT = 70;
            } // onlineReservations ones get more benefit of the doubt if not many reviews
            else {
                $TEND_TOWARDS_PERCENT = ($this->preferredBooking ? 85 : 78);
            }
            // bascially a weighted average of $DAMPER_COUNT # of reviews at $TEND_TOWARDS_PERCENT, combined with actual reviews
            $this->combinedRating = round(($DAMPER_COUNT * $TEND_TOWARDS_PERCENT + $scoreTotal) / ($DAMPER_COUNT + $weightedCountTotal));
            $this->combinedRatingCount = $combinedRatingCount;
        }

        $output .= "-> Combined Result: $this->combinedRating ($this->combinedRatingCount count)";

        return $output;
    }

    // Note: This method doesn't save the address changes, just updates the listing object's fields.

    public function addressFix($alsoDoMoreIntensiveFixes = false): bool
    {
        $this->output .= '(' . $this->elapsedTime() . "s) Address fix.\n";

        $originalListing = $this;

        // Set Continent
        if ($this->country !== '' && $this->continent === '') {
            $continent = CountryInfo::where('country', $this->country)->value('continent');
            if ($continent !== '') {
                $this->continent = $continent;
            }
        }

        // * Capitalize *
        // (must do this first so we don't introduce our own capitalized words below)

        // Address

        // If all lowercase or uppercase (and has a word of at least 4 characters)...
        if ($this->address !== '' && ($this->address === mb_strtolower($this->address) || $this->address === mb_strtoupper($this->address)) && preg_match('/\pL{4,}/', $this->address)) {
            $this->address = mb_convert_case($this->address, MB_CASE_TITLE);
        } // capitalize words

        // City

        // If all lowercase or uppercase (and has a word of at least 4 characters)...
        if ($this->city !== '' && ($this->city === mb_strtolower($this->city) || $this->city === mb_strtoupper($this->city))) {
            $this->city = mb_convert_case($this->city, MB_CASE_TITLE);
        } // capitalize words

        // * Replace Anywhere *
        $replaceAnywhere = ["\n" => ', ', "\r" => ', ', ' ,' => ',', '  ' => ' ', ',,' => ',', '..' => '.', ', (' => ' (', '( ' => '(', ' )' => ')', ', ,' => ', ', '()' => '',
            'straße' => 'strasse', ];

        foreach ($replaceAnywhere as $needle => $haystack) {
            $this->address = mb_stri_replace($needle, $haystack, $this->address);
        }

        // * Comma Separated Section Replace *
        $sectionSeparators = [','];
        $sectionReplace = [$this->name => '', $this->city => '', $this->cityAlt => '', $this->zipcode => '', $this->region => '', $this->country => ''];
        // Add ones for county abbreviated (a lot of these in Scotland, etc.)

        if (strpos($this->region, 'County') !== false) {
            $sectionReplace[str_replace('County', 'Co', $this->region)] = '';
            $sectionReplace[str_replace('County', 'Co.', $this->region)] = '';
            $sectionReplace[mb_trim(str_replace('County', '', $this->region))] = '';
        }

        $addressSections = multiExplode($sectionSeparators, false, $this->address);
        foreach ($addressSections as $sectionKey => $section) {
            $trimmedSection = mb_trim($section);
            if ($trimmedSection === '') {
                continue;
            }

            foreach ($sectionReplace as $from => $to) {
                if ($from !== '' && mb_strtolower($trimmedSection) === mb_trim(mb_strtolower($from))) {
                    $addressSections[$sectionKey] = $to;
                }
            }

            // * District to cityAlt *
            if ($this->cityAlt === '') {
                if ((preg_match('/^.+ district$/i', $trimmedSection) || preg_match('/^Barrio .+$/', $trimmedSection)) && mb_strlen($section) < 20) {
                    $addressSections[$sectionKey] = '';
                    $this->cityAlt = $trimmedSection;
                } elseif ($alsoDoMoreIntensiveFixes) {
                    // See if there is a cityAlt in this city matching this string
                    if (Listing::where('city', $this->city)->where('country', $this->country)->where('cityAlt', $trimmedSection)->count()) {
                        /* could also check for old values in dataVerification */
                        $addressSections[$sectionKey] = '';
                        $this->cityAlt = mb_trim($section);
                    }
                }
            }
        }
        $this->address = implode('', $addressSections);

        // * Word Replace *
        $wordSeparators = [' ', ',', ';'];
        $wordStartSeparators = ['c/o', 'C/', 'c/']; // (c/o is here just so it doesn't get confused with a c/ addresses)
        $wholeWordReplace = ['-unknown-' => '', 's/n' => '', 'n/a' => '', 'Bª' => 'Barrio', 'Avda.' => 'Avenida', 'Avda' => 'Avenida',
            'C/' => 'Calle ' /* needs space after cuz is separator also */, 'Cir' => 'Circle', 'Cir.' => 'Circle', 'Ct.' => 'Court',
            'Rd.' => 'Road', 'Rd' => 'Road', 'Ave.' => 'Avenue', 'Ave' => 'Avenue', 'Tce.' => 'Terrace', 'Tce' => 'Terrace', 'Terr.' => 'Terrace',
            'Hwy.' => 'Highway', 'Hwy' => 'Highway', 'Dist.' => 'District', ]; // 'St.'=>'Street', 'St'=>'Street'-> but what about Saint abbreviations?

        $addressWords = multiExplode($wordSeparators, $wordStartSeparators, $this->address);
        foreach ($addressWords as $wordKey => $word) {
            foreach ($wholeWordReplace as $from => $to) {
                if (mb_strtolower($word) === mb_strtolower($from)) {
                    $addressWords[$wordKey] = $to;
                }
            }
        }
        $this->address = implode('', $addressWords);

        // * Misc *
        $this->address = mb_eregi_replace('(km\.?) ([[:digit:]]+),([[:digit:]]+)', '\\1 \\2.\\3', $this->address); // '.' as decimal separator in km distance
        $this->address = mb_ereg_replace(',([^[:space:]])', ', \\1', $this->address); // space after a comma
        $this->address = mb_ereg_replace('([^[:space:]])/[[:space:]]', '\\1 / ', $this->address); // space missing before slash
        $this->address = mb_ereg_replace('[[:space:]]/([^[:space:]])', ' / \\1', $this->address); // space missing after slash
        $this->address = mb_ereg_replace('([^[:space:]])\(', '\\1 (', $this->address); // space missing before (
        $this->address = mb_eregi_replace('($|\b)n\. ?([[:digit:]]+)', '\\2', $this->address); // "n." in address #
        $this->address = mb_eregi_replace('($|\b)n\: ?([[:digit:]]+)', '\\2', $this->address); // "n:" in address #
        $this->address = mb_eregi_replace('($|\b)No\.([[:digit:]]+)', '\\2', $this->address); // "No.5"
        $this->address = mb_eregi_replace('($|\b)No\: ?([[:digit:]]+)', '\\2', $this->address); // "No:5"
        $this->address = mb_eregi_replace('($|\b)Nr\. ?([[:digit:]]+)', '\\2', $this->address); // "Nr.5"
        $this->address = mb_eregi_replace('($|\b)No ?([[:digit:]]+)', '\\2', $this->address); // "No5"
        $this->address = mb_eregi_replace('($|\b)Nr ?([[:digit:]]+)', '\\2', $this->address); // "Nr5"

        // * PO Box *
        // (using non-multibyte preg_match is fine for this)
        if (preg_match('/P\.? ?O\.? ?Box ?(\d+)/i', $this->address, $matches)) {
            $this->poBox = "PO Box $matches[1]"; // note this overwrites and existing PO Box value. oh well.
            $this->address = preg_replace('/(P\.? ?O\.? ?Box ?\d+)/i', '', $this->address);
        }
        if (preg_match('/(^|\s)P\.?O\.?B\.? ?(\d+)/i', $this->address, $matches)) {
            $this->poBox = "POB $matches[2]"; // note this overwrites and existing PO Box value. oh well.
            $this->address = preg_replace('/(^|\s)(P\.?O\.?B\.? ?\d+)/i', '', $this->address);
        }
        if (preg_match('/^Box ?(\d+)/i', $this->address, $matches)) {
            $this->poBox = "Box $matches[1]"; // note this overwrites and existing PO Box value. oh well.
            $this->address = preg_replace('/^(Box ?\d+)/i', '', $this->address);
        }

        // * Trim *
        $trimCharList = ' ,';
        $this->address = mb_trim($this->address, $trimCharList);
        $this->city = mb_trim($this->city, $trimCharList);
        $this->cityAlt = mb_trim($this->cityAlt, $trimCharList);
        $this->zipcode = mb_trim($this->zipcode, $trimCharList);

        // * Entire String *
        $entireString = ['n/a' => '', 'na' => '', 'Call for reservations' => '', 'Call for address' => ''];
        foreach ($entireString as $from => $to) {
            if (mb_strtolower($this->address) === mb_strtolower($from)) {
                $this->address = $to;
            }
            if (mb_strtolower($this->zipcode) === mb_strtolower($from)) {
                $this->zipcode = $to;
            }
        }

        // * Remove Duplicate Info *
        if (! strcasecmp($this->address, $this->cityAlt)) {
            $this->address = '';
        }
        if (! strcasecmp($this->address, $this->city)) {
            $this->address = '';
        }
        if (! strcasecmp($this->address, $this->region)) {
            $this->address = '';
        }
        if (! strcasecmp($this->address, $this->country)) {
            $this->address = '';
        }
        if (! strcasecmp($this->cityAlt, $this->city)) {
            $this->cityAlt = '';
        }
        if (! strcasecmp($this->cityAlt, $this->region)) {
            $this->cityAlt = '';
        }
        if (! strcasecmp($this->cityAlt, $this->country)) {
            $this->cityAlt = '';
        }

        // * Check for changes *
        $addressFields = ['address', 'mapAddress', 'cityAlt', 'city', 'region', 'country', 'continent', 'poBox', 'zipcode'];
        foreach ($addressFields as $addressField) {
            // If there were any changes made, we run it again in case those changes result in other potential changes.
            if ($this->$addressField !== $originalListing->$addressField) {
                $this->addressFix();
            }

            return true; // true means changes were made
        }

        return false; // false means no changes were made
    }

    // Updates the originality "score" of owner-submitted texts by comparing them to the imported texts.

    public function updateOriginalityScores()
    {
        $output = '';

        if ($this->attachedTexts->isEmpty()) {
            return 'None.';
        }

        // Get all the imported text.
        // Note that this doesn't check type, so it compares each one to all types of imported text for the listing
        $otherTexts = [];
        foreach ($this->importeds as $imported) {
            foreach ($imported->attachedTexts as $importedText) {
                if (! in_array($importedText->type, ['description', 'location'])) {
                    continue;
                }
                $otherTexts[] = $importedText->data;
            }
        }

        foreach ($this->attachedTexts as $ownerText) {
            if (! in_array($ownerText->type, ['description', 'location'])) {
                continue;
            }

            $maxScore = 0;
            foreach ($otherTexts as $otherText) {
                $percent = 0;
                similar_text(strip_tags($ownerText->data), strip_tags($otherText), $percent);
                if ($percent > $maxScore) {
                    $maxScore = round($percent);
                }
            }
            $score = 100 - $maxScore; // make higher numbers better (originality score rather than similarity percent)
            if ($score === 0) {
                $score = 1;
            } // minimum score of 1, to indicate that we did check it
            $ownerText->score = $score;
            $ownerText->save();
            $output .= "$ownerText->type ($ownerText->language) originality score: $score%. ";
        }

        return $output;
    }

    public function compileFeatures(): void
    {
        $this->output .= '(' . $this->elapsedTime() . 's) Compile features: ';

        // ListingFeatures::merge() gives precidence to the first parameter features,
        // so this gives precidence to mgmtFeatures, then active importeds, then inactive importeds.

        $importedFeatures = [];
        foreach ($this->listing->activeImporteds as $imported) {
            if (! $imported->features) {
                continue;
            }
            $importedFeatures = ListingFeatures::merge($importedFeatures, $imported->features, true);
        }

        foreach ($this->listing->inactiveImporteds as $imported) {
            if (! $imported->features) {
                continue;
            }
            $importedFeatures = ListingFeatures::merge($importedFeatures, $imported->features, true);
        }

        $importedFeatures['goodFor'] = $this->setGoodFor($importedFeatures);

        // ('false' is passed so that if mgmtFeatures has checkboxes for an item, the imported checkbox options are ignored rather than merged.)
        $this->listing->compiledFeatures = ListingFeatures::merge(
            $this->listing->mgmtFeatures,
            $importedFeatures,
            false
        );

        $this->output .= json_encode($this->listing->compiledFeatures) . "\n";
    }

    private function setGoodFor($features)
    {
        // If mgmt set "goodFor", then let that take precidence and ignore any additional "goodFor" options from importeds.
        if (! empty($this->mgmtFeatures['goodFor'])) {
            return [];
        }

        if (! isset($features['goodFor'])) {
            $features['goodFor'] = [];
        }

        //  female_solo_traveller
        if (! in_array('female_solo_traveller', $features['goodFor']) && (
            (isset($this->mgmtFeatures['gender']) && $this->mgmtFeatures['gender'] === 'femaleOnly') ||
            (isset($features['extras']) && $this->someInExtras(['yoga_classes'], $features['extras']))
        )
        ) {
            $features['goodFor'][] = 'female_solo_traveller';
        }

        if (! isset($features['goodFor'])) {
            $features['goodFor'] = [];
        }

        //  partying
        if (
            ! in_array('partying', $features['goodFor']) &&
            (
                (isset($this->mgmtFeatures['pubCrawls']) && $this->mgmtFeatures['pubCrawls'] !== 'no') ||
                (
                    isset($features['extras']) &&
                    $this->someInExtras(['bar', 'nightclub'], $features['extras'])
                )
            )
        ) {
            $features['goodFor'][] = 'partying';
        }

        //  business
        if (
            ! in_array('business', $features['goodFor']) &&
            (
                isset($features['extras']) &&
                $this->someInExtras(['gym', 'meeting_banquet_facilities'], $features['extras'])
            )
        ) {
            $features['goodFor'][] = 'business';
        }

        // socializing => Solo-Traveler
        if (
            ! in_array('socializing', $features['goodFor']) &&
            (
                (isset($features['pubCrawls']) && $features['pubCrawls'] !== 'no') ||
                (isset($features['tours']) && $features['tours'] !== 'no') ||
                (
                    isset($features['extras']) &&
                    $this->someInExtras(
                        ['darts', 'karaoke', 'karaoke', 'table_tennis', 'evening_entertainment',
                            'meeting_banquet_facilities', 'walking_tours', 'bike_tours', 'themed_dinner_nights',
                            'tour_class_local_culture', 'live_music_performance', 'gameroom', 'pooltable', ],
                        $features['extras']
                    )
                )
            )
        ) {
            $features['goodFor'][] = 'socializing';
        }

        if (! isset($features['goodFor'])) {
            return [];
        }

        if (! is_array($features['goodFor'])) {
            return $features['goodFor'];
        }

        if (count($features['goodFor']) === 1) {
            return $features['goodFor'];
        }

        if (($key = array_search('families', $features['goodFor'])) !== false && in_array('partying', $features['goodFor'])) {
            unset($features['goodFor'][$key]);
        }

        if (($key = array_search('quiet', $features['goodFor'])) !== false && in_array('partying', $features['goodFor'])) {
            unset($features['goodFor'][$key]);
        }

        return array_unique($features['goodFor']);
    }

    private function someInExtras(array $items = [], array $extras = [])
    {
        foreach ($items as $item) {
            if (in_array($item, $extras)) {
                return true;
            }
        }

        return false;
    }

    // Sets $this->latitude and $this->longitude based on $imported and $this->ownerLatitude, $this->ownerLongitude

    public function setBestGeocoding(): void
    {
        $this->output .= '(' . $this->elapsedTime() . 's) Geocoding: ';

        if ($this->listing->geocodingLocked) {
            $this->output .= '(locked)';

            return;
        }

        $bestLat = 0;
        $bestLong = 0;
        $bestTrustability = false;

        if ((float) $this->listing->ownerLatitude !== 0.0 || (float) $this->listing->ownerLongitude !== 0.0) {
            $this->output .= 'Owner (trustability: ' . ImportSystems::$OTHER_GEO_TRUSTABILITY['owner'] . "): {$this->listing->ownerLatitude},{$this->listing->ownerLongitude}. ";

            $bestLat = $this->listing->ownerLatitude;
            $bestLong = $this->listing->ownerLongitude;
            $bestTrustability = ImportSystems::$OTHER_GEO_TRUSTABILITY['owner'];
        }

        foreach ($this->listing->importeds as $imported) {
            $trustability = $imported->getImportSystem()->geocodingTrustability;
            $this->output .= $imported->getImportSystem()->shortName() . " (trustability: $trustability): $imported->latitude,$imported->longitude. ";
            if (($imported->hasLatitudeAndLongitude()) && ($bestTrustability === false || $trustability > $bestTrustability)) {
                $bestLat = $imported->latitude;
                $bestLong = $imported->longitude;
                $bestTrustability = $trustability;
            }
        }

        $geocodingResult = $this->fetchGeocodeByAddress();
        $this->output .= 'Our geocoding: ';

        if ($geocodingResult) {
            $this->output .= "\"$geocodingResult[addressString]\" trustability: " . Geocoding::accuracyName($geocodingResult['accuracy']) . ' ';

            if (isset($geocodingResult['latitude'])) {
                $this->output .= "$geocodingResult[latitude],$geocodingResult[longitude]. ";
            }

            if ($geocodingResult['accuracy'] === Geocoding::$ACCURACY_TYPE['latLongLookup']) {
                logWarning("We're unlikely to exactly match the address string to get a cached latLongLookup result, but did (listing {$this->listing->id}).");
            }

            if (($geocodingResult['accuracy'] === Geocoding::$ACCURACY_TYPE['rooftop'] &&
                    ImportSystems::$OTHER_GEO_TRUSTABILITY['geocodedRooftop'] > $bestTrustability) ||
                ($geocodingResult['accuracy'] >= Geocoding::$ACCURACY_TYPE['interpolated'] &&
                    ImportSystems::$OTHER_GEO_TRUSTABILITY['geocodedinterpolated'] > $bestTrustability) ||
                ($geocodingResult['accuracy'] === Geocoding::$ACCURACY_TYPE['approxStreet'] && $bestTrustability === false)
            ) {
                $bestLat = $geocodingResult['latitude'];
                $bestLong = $geocodingResult['longitude'];
                $bestTrustability = '[our geocoding]';
            }
        } else {
            $this->output .= '(no result). ';
        }

        $this->output .= "Best Lat/long (trusted: $bestTrustability): $bestLat,$bestLong. ";

        // Set Results
        $this->listing->latitude = round($bestLat, Listing::LATLONG_PRECISION);
        $this->listing->longitude = round($bestLong, Listing::LATLONG_PRECISION);

        $this->output .= "\n";
    }

    public function fetchGeocodeByAddress(): array|bool
    {
        return Geocoding::geocode(
            $this->mapAddress !== '' ? $this->mapAddress : $this->address,
            $this->city,
            $this->region,
            $this->country
        );
    }

    // Returns a property type or "(unknown)" if it can't determine the most likely type.

    public function determineMostProbablePropertyType(&$output = '')
    {
        $output = '';

        // Check its property type in imported (this should be after we do checkListingAvail()) for dates within next or previous several months
        $hasDormAvail = $this->hadAvailabilityOfType('dorm');
        $hasPrivateAvail = $this->hadAvailabilityOfType('private');
        $hasNoAvailabilityAtAll = (! $hasDormAvail && ! $hasPrivateAvail);

        $importedListsAsNotHostel = $importedListsAsHostel = false;
        $importedListsAsPropertyType = '';
        foreach ($this->activeImporteds as $imported) {
            $systemInfo = $imported->getImportSystem();
            if ($systemInfo->propertyTypeAccuracy >= 5) {
                if ($imported->propertyType === 'Hostel') {
                    $importedListsAsHostel = true;
                } else {
                    $importedListsAsNotHostel = true;
                    $importedListsAsPropertyType = $imported->propertyType;
                }
            }
        }

        $hasLiveReview = ($this->getLiveReview('') !== null);

        /*
            General concepts:
            - If we're unsure, just let it return '(unknown)'.
            - Don't make a non-hostel a hostel unless it has dorm bed availability. (Too many old listings not in bookings systems would become live.)
        */

        // Note:  Camping/apartment/other property types aren't changed since those are likely accurate.
        if ($this->propertyType === 'Campsite' || $this->propertyType === 'Apartment' || $this->propertyType === 'Other') {
            $apparentPropertyType = $this->propertyType;
        } // Campsite names
        elseif (stripos($this->name, 'Camping') !== false || stripos($this->name, 'Campground') !== false || stripos($this->name, 'Campsite') !== false) {
            $apparentPropertyType = 'Campsite';
        } // "Hostel"/"Backpackers" name with dorms
        elseif ($hasDormAvail && (stripos($this->name, 'Hostel') !== false || stripos($this->name, 'Backpackers') !== false)) {
            $apparentPropertyType = 'Hostel';
        } // "Apartment" name
        elseif (! $hasDormAvail && stripos($this->name, 'Apartment') !== false) {
            $apparentPropertyType = 'Apartment';
        } // Hostel based on availability
        elseif ($hasDormAvail && stripos($this->name, 'Hotel') === false) {
            $apparentPropertyType = 'Hostel';
        } // No dorms, but has private rooms, so probably a hotel.
        elseif ($hasPrivateAvail && ! $hasDormAvail && ! $hasLiveReview) {
            $apparentPropertyType = 'Hotel';
        } // No availability, but all booking systems list as not a hostel, so probably a hotel.
        elseif ($hasNoAvailabilityAtAll && ! $importedListsAsHostel && $importedListsAsNotHostel && ! $hasLiveReview) {
            $apparentPropertyType = $importedListsAsPropertyType;
        } // No availability, has a hotel-like name...
        elseif ($hasNoAvailabilityAtAll && ! $hasLiveReview &&
            stripos($this->name, 'Hostel') === false && stripos($this->name, 'Backpackers') === false &&
            (stripos($this->name, 'Hotel') !== false || stripos($this->name, 'B&B') !== false || stripos($this->name, 'B & B') !== false ||
                stripos($this->name, 'Guesthouse') !== false || stripos($this->name, 'Guest House') !== false)) {
            $apparentPropertyType = 'Hotel';
        } else {
            $apparentPropertyType = '(unknown)';
        }

        // Change 'hotel' to 'guesthouse' in some cases...
        if ($apparentPropertyType === 'Hotel' && ($this->propertyType === 'Guesthouse' ||
                stripos($this->name, 'Guesthouse') !== false || stripos($this->name, 'Guest House') !== false)) {
            $apparentPropertyType = 'Guesthouse';
        }

        $output .= "current:'$this->propertyType' (verified:" . ($this->propertyTypeVerified ? 'yes' : 'no') .
            '), hasDormAvail:' . ($hasDormAvail ? 'yes' : 'no') . ', hasPrivateAvail:' . ($hasPrivateAvail ? 'yes' : 'no') .
            ', hasLiveReview:.' . ($hasLiveReview ? 'yes' : 'no') .
            ' importedListsAsHostel:' . ($importedListsAsHostel ? 'yes' : 'no') . ', importedListsAsNotHostel:' . ($importedListsAsNotHostel ? 'yes' : 'no') .
            " =&gt; apparentPropertyType:'$apparentPropertyType'";

        return $apparentPropertyType;
    }

    // ** Snippets **

    public function updateSnippets(): array
    {
        if ($this->listing->blockSnippet) {
            return [];
        }

        $snippets = [];
        foreach (Languages::allLiveSiteCodes() as $language) {
            $snippet = '';
            $enoughText = false;

            // Reviews
            if (! $enoughText && $review = $this->listing->getLiveReview($language)) {
                $enoughText = $this->snippetAdd($snippet, $this->startFromRandomSentence($review->editedReview), true);
            }

            // Comments
            if (! $enoughText) {
                $ratingsText = Rating::getRatingsForListing($this, $language, true, true)
                    ->sort(function ($a, $b) {
                        if ($a->rating !== $b->rating) {
                            return $b->rating - $a->rating;
                        } // use the best ratings

                        return $b->id - $a->id; // prefer newer ones
                    })
                    ->slice(0, 5) // choose text from the best comments only
                    ->reduce(function ($carry, $rating) {
                        if ($carry !== '') {
                            $carry .= ' ';
                        }

                        return $carry . ($rating->summary !== '' ? "$rating->summary - " : '') . $rating->comment;
                    });

                $enoughText = $this->snippetAdd($snippet, $this->startFromRandomSentence($ratingsText), true);
                if ($enoughText) {
                    break;
                }
            }

            // Description

            if (! $enoughText) {
                $text = $this->listing->getText('description', $language, false, true);
                if ($text) {
                    $enoughText = $this->snippetAdd($snippet, $this->startFromRandomSentence($text->data), true);
                }
            }

            // Location

            if (! $enoughText) {
                $text = $this->listing->getText('location', $language, false, true);
                if ($text) {
                    $enoughText = $this->snippetAdd($snippet, $this->startFromRandomSentence($text->data), true);
                }
            }

            // Use the previously generated English snippet

            // (must do this before we add non-language stuff like address, etc. below)
            if ($language === 'en') {
                $englishTextSnippet = $snippet;
            } elseif (! $enoughText && $englishTextSnippet) {
                $this->snippetAdd($snippet, $englishTextSnippet, false);
            }

            // Address
            if (! $enoughText && $this->address !== '') {
                $enoughText = $this->snippetAdd($snippet, $this->address, false);
            }

            /* having the full address was causing google to show a map link on city search results
    		// City / Country (nothing better to display)
    		if (!$enoughText)
    			$enoughText = $this->snippetAdd($snippet, "$listing->city, $listing->country", false); */

            $snippets[$language] = $snippet;
        }

        AttachedText::replaceAllLanguages('hostels', $this->id, null, 'snippet', '', $snippets, false);

        return $snippets;
    }

    private function startFromRandomSentence($text)
    {
        $sentences = array_values(array_filter(array_map('trim', preg_split('/[\n\.]+/s', $text))));
        if (! $sentences) {
            return '';
        }
        $randomSentence = array_rand($sentences);
        // Make sure it isn't too close to the last end.
        if ($randomSentence > count($sentences) - 4) {
            $randomSentence = max(0, $randomSentence - 3);
        }

        return ($randomSentence > 0 ? self::SNIPPET_ELLIPSIS : '') . substr($text, strpos($text, $sentences[$randomSentence]));
    }

    private function startTruncate($s, $amount)
    {
        if (mb_strlen($s) <= $amount) {
            return $s;
        }
        $s = mb_substr($s, $amount);
        if (($breakpoint = mb_strpos($s, ' ')) !== false) {
            $s = mb_substr($s, $breakpoint);
        }

        return $s;
    }

    private function snippetAdd(&$snippet, $s, $quote)
    {
        $s = trim(strip_tags($s));
        if ($s === '') {
            return false;
        }
        if ($quote) {
            $s = '"' . $s . '"';
        }
        if ($snippet !== '' && strpos($s, trim(self::SNIPPET_ELLIPSIS)) !== 0) { // (makes sure new string doesn't already start w/ ellipsis)
            $snippet .= self::SNIPPET_ELLIPSIS;
        }
        $snippet = mb_substr($snippet . $s, 0, self::SNIPPET_LENGTH);
        if (strlen($snippet) === self::SNIPPET_LENGTH) {
            return true;
        }

        return false;
    }

    private function elapsedTime()
    {
        static $lastTime = 0;

        $now = time();
        $difference = ($lastTime ? $now - $lastTime : 0);
        $lastTime = $now;

        return $difference;
    }

    private static function makeLocationDataCorection(string $output): string
    {
        $output .= "\nCountry Data Corrections: ";
        $output .= DataCorrection::correctAllDatabaseValues(
            '',
            'country',
            Listing::query(),
            Listing::$staticTable,
            null,
            Listing::$dataCorrectionContexts['country'][0],
            Listing::$dataCorrectionContexts['country'][1]
        );
        $output .= "\nRegion Data Corrections: ";
        $output .= DataCorrection::correctAllDatabaseValues(
            '',
            'region',
            Listing::query(),
            Listing::$staticTable,
            null,
            Listing::$dataCorrectionContexts['region'][0],
            Listing::$dataCorrectionContexts['region'][1]
        );
        $output .= "\nCity Data Corrections: ";
        $output .= DataCorrection::correctAllDatabaseValues(
            '',
            'city',
            Listing::query(),
            Listing::$staticTable,
            null,
            Listing::$dataCorrectionContexts['city'][0],
            Listing::$dataCorrectionContexts['city'][1]
        );
        $output .= "\nNeighborhood Data Corrections: ";
        $output .= DataCorrection::correctAllDatabaseValues(
            'listings',
            'cityAlt',
            Listing::query(),
            Listing::$staticTable,
            null,
            Listing::$dataCorrectionContexts['cityAlt'][0],
            Listing::$dataCorrectionContexts['cityAlt'][1]
        );

        return $output;
    }

    public static function setContinent(string $output = ''): string
    {
        $output .= "\nSet Continent: ";

        foreach (Listing::where('continent', '')->groupBy('country')->pluck('country') as $country) {
            $continent = CountryInfo::where('country', $country)->value('continent');
            if ($continent === '') {
                continue;
            } // (just ignore it - but warnings are issued for cityInfos with unknown countries)
            $output .= "['$country' -> '$continent'] ";
            Listing::where('country', $country)->update(['continent' => $continent]);
        }

        $output .= "\n";

        return $output;
    }

    private function updateActiveImportedStatus(): void
    {
        $this->output .= '(' . $this->elapsedTime() . "s) update Active Imported Status.\n";

        $this->listing->getAllActiveSystemImporteds()
            ->each(fn (Imported $imported) => $imported->updateStatus());

        $this->listing->refresh();
    }

    public function updateActiveImporteds(): void
    {
        $this->output .= '(' . $this->elapsedTime() . 's) Update imported data: ';

        foreach ($this->activeImporteds as $imported) {
            /** @var Imported $imported */
            $this->output .= "$imported->id (" . $imported->getImportSystem()->shortName() . ') ';
            $imported->updateData();
        }
    }

    private function downloadImportedPics(): void
    {
        foreach ($this->activeImporteds as $imported) {
            /** @var Imported $imported */
            DownloadPicsImportedJob::dispatch($imported)->onQueue('sync');

            $this->output .= "in queue! $imported->id (" . $imported->getImportSystem()->shortName() . ') (' . $this->elapsedTime() . 's) ';
        }
    }

    private function findDuplicatedListings(mixed $allowAutoMerging): bool
    {
        $this->output .= '(' . $this->elapsedTime() . 's) Find Listing Duplicates: ';

        $didAutoMerge = false;

        $outputTemp = ListingDuplicate::findDuplicates(
            $this,
            $allowAutoMerging,
            false,
            72,
            $didAutoMerge
        );

        $this->output .= ($outputTemp === '' ? '(none)' : $outputTemp) . "\n";

        return $didAutoMerge;
    }

    private function updateWebsiteInfo(bool $quickMode = false): void
    {
        $this->output .= '(' . $this->elapsedTime() . 's) Update website status: ';

        if ($this->listing->web === '' && ! $this->listing->activeImporteds->isEmpty()) {
            // See if we an imported record has a website we can use for this listing
            foreach ($this->listing->activeImporteds as $imported) {
                if ($imported->web !== '' && filter_var($imported->web, FILTER_VALIDATE_URL) &&
                    $imported->web !== 'http://www.yha.co.nz/' /* too generic */
                ) {
                    $this->listing->web = $imported->web;
                    $this->output .= "(using website '$imported->web' from " . $imported->getImportSystem()->shortName() . ')) ';

                    break;
                }
            }
        }

        if ($this->listing->web !== '') {
            $this->listing->webStatus = WebsiteStatusChecker::getWebsiteStatus($this->listing->web, true, true);
        } else {
            $this->listing->webStatus = WebsiteStatusChecker::$websiteStatusOptions['unknown'];
        }

        $this->output .= "'$this->listing->web' " . WebsiteStatusChecker::statusDisplayString($this->listing->webStatus) . "\n";

        if ($this->listing->webStatus === WebsiteStatusChecker::$websiteStatusOptions['ok'] && $this->listing->isLive()) {
            $this->output .= '(' . $this->elapsedTime() . "s) Spider website '$this->listing->web': ";
            if ($quickMode) {
                $this->output .= "Skipping (quick mode)\n";
            } else {
                $spiderResults = $this->spiderWebsite();
                $this->output .= ($spiderResults ? implode(', ', array_keys($spiderResults)) : 'None') . "\n";
            }
        }
    }

    private function updatePropertyType(): void
    {
        if (! $this->listing->propertyTypeVerified) {
            $outputTemp = '';

            $shouldBePropertyType = $this->determineMostProbablePropertyType($outputTemp);

            $this->output .= '(' . $this->elapsedTime() . "s) Verify/update property Type: $outputTemp. ";

            if ($this->listing->propertyType !== $shouldBePropertyType && $shouldBePropertyType !== '(unknown)') {
                $this->listing->propertyType = $shouldBePropertyType;
                $this->output .= "Changing to '$shouldBePropertyType'.";
            } else {
                $this->output .= 'Leaving unchanged.';
            }

            $this->output .= "\n";
        }
    }

    /**
     * @param $output
     * @return mixed
     */
    private function updatePriorityLevel(): void
    {
        $this->output .= '(' . $this->elapsedTime() . 's) Updating priorityLevels of related Listing Duplicates: ';
        ListingDuplicate::forListingID($this->id)
            ->get()
            ->each(function (ListingDuplicate $duplicate) {
                $duplicate->setPriorityLevel()->save();
                $this->output .= "$duplicate->id ($duplicate->priorityLevel) ";
            });

        $this->output .= "\n";
    }
}
