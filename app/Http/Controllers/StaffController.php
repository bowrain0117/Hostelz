<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\EventLog;
use App\Helpers\MailFetch;
use App\Helpers\UserSettingsHandler;
use App\Models\Ad;
use App\Models\Article;
use App\Models\AttachedText;
use App\Models\Booking;
use App\Models\CityComment;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Geonames;
use App\Models\Imported;
use App\Models\Languages;
use App\Models\LanguageString;
use App\Models\Listing\Listing;
use App\Models\Macro;
use App\Models\MailMessage;
use App\Models\Pic;
use App\Models\Rating;
use App\Models\Review;
use App\Models\SavedList;
use App\Models\SearchRank;
use App\Models\User;
use App\Services\AjaxDataQueryHandler;
use App\Services\Payments;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Lib\DataCorrection;
use Lib\DiskSize;
use Lib\FileListHandler;
use Lib\FileUploadHandler;
use Lib\FormHandler;
use Lib\Geocoding;
use Lib\Middleware\BrowserCache;
use Lib\PageCache;
use Lib\TranslationService;

// not sure why this one needs the full path, but it does.

/*
    2 columns: FormHandler::displayFieldsInColumns(..., 2)
*/

class StaffController extends Controller
{
    public function index()
    {
        $message = '';

        // Misc commands
        if (Request::has('command')) {
            BrowserCache::$disabled = true;

            switch (Request::input('command')) {
                case 'insertListingsFromImporteds':
                    $message = 'Creating New Listings From Imported: ';
                    $importeds = Imported::where('hostelID', 0)->where('country', '!=', '')
                        ->where('propertyType', '!=', '')->where('status', 'active')->limit(1000)->get();
                    if ($importeds->count() == 1000) {
                        $message .= '<h4>(ONLY INSERTING THE FIRST 1000. RUN AGAIN AFTER TO CONTINUE.)</h4>';
                    }
                    foreach ($importeds as $imported) {
                        $listing = $imported->createListing();
                        $message .= $listing->id . ' (from ' . $imported->getImportSystem()->shortName() . ') ';
                    }

                    break;
            }
        }

        // Admin Only
        if (auth()->user()->hasPermission('admin') && Request::has('command')) {
            switch (Request::input('command')) {
                case 'updateLangFiles':
                    $output = LanguageString::updateLangFiles();
                    $message = '<pre>' . htmlspecialchars($output) . '</pre>';

                    break;

                case 'geonamesDownload':
                    $output = Geonames::downloadData();
                    $message = '<pre>' . htmlspecialchars($output) . '</pre>';

                    break;

                case 'geonamesImportDownloadedTest':
                    $output = Geonames::importData(true);
                    $message = '<pre>' . htmlspecialchars($output) . '</pre>';

                    break;

                case 'geonamesImportDownloaded':
                    $output = Geonames::importData(false);
                    $message = '<pre>' . htmlspecialchars($output) . '</pre>';

                    break;

                case 'geonamesImportDownloadedReset':
                    $output = Geonames::resetDataImportDataCompletedCounters();
                    $message = 'Data import counters reset.';

                    break;

                case 'mailFetch':
                    $output = MailFetch::fetchNew();
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'mailSendQueued':
                    $output = MailMessage::sendQueuedMessages();
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'afterListingDataImportMaintenance':
                    Artisan::call('hostelz:websiteMaintenance', ['period' => 'afterListingDataImport']);
                    $output = Artisan::output();
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'importAll':
                    $output = shell_exec('cd ' . base_path() . ' && ' .
                        'nohup bash -c \'' . // to make the commands run in the background
                        'php artisan hostelz:importedImport "" && ' .
                        'php artisan hostelz:createListingsFromImporteds && ' .
                        'php artisan hostelz:websiteMaintenance afterListingDataImport ' .
                        '\' ' . '> /dev/null 2>/dev/null &');
                    $message = '<pre>Running import all.</pre>';

                    break;
            }
        }

        $diskInfo = DiskSize::getRootDiskInfo();

        // Developer Only
        if (auth()->user()->hasPermission('developer') && Request::has('command')) {
            switch (Request::input('command')) {
                case 'opcacheInfo':
                    $message = '<pre>' . print_r(opcache_get_status(), true) . '</pre>';

                    break;

                case 'clearAllPageCache':
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                    }
                    Artisan::call('view:clear'); // Laravel's view cache

                    if (App::environment() == 'production') {
                        $commandOutput = shell_exec(
                            'cd ' . base_path() . ';' .
                            'composer dump-autoload --no-dev 2>&1'
                            /*
                            (was going to have it run "gulp --production", but can't unless we sync the node_modules and bower_components,
                            which slows down syncing.  so we just assume gulp is run as --production on the dev server before syncing)
                            'export HOME=/home/hostelz/dev/storage/gulpTemp && export DISABLE_NOTIFIER=true && gulp --production 2>&1'
                            */
                        );
                        //  lluminate\Foundation\Console/OptimizeClearCommand
                        Artisan::call('optimize:clear');

//                        Artisan::call('view:cache');
//                        Artisan::call('optimize');
                    }

                    PageCache::clearAll();

                    // $message = "Page cache cleared.";

                    // (Displaying a view template just after clearing the cache seems to not work.  So just displaying a plain text message instead.
                    return 'Page cache cleared.';

                    break;

                case 'gulp':
                case 'gulpProduction':
                    $command = 'cd ' . base_path() . ' && export HOME=' . config('custom.gulpTemp') . ' && export DISABLE_NOTIFIER=true && ' . config('custom.gulpExecutable') . ' ' .
                        (Request::input('command') == 'gulpProduction' ? '--production' : '') . ' 2>&1';

                    $output = shell_exec($command);

                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'query':
                    $message = '<pre>';
                    $result = DB::select(Request::input('inputBox'));
                    $output = print_r($result, true);
                    $message .= htmlspecialchars($output);
                    //$result = dbGetAll('SHOW WARNINGS');
                    //if(!$result) echo dbError(); else print_r($result);
                    $message .= '</pre>';

                    break;

                case 'showPhpErrors':
                    $output = shell_exec('tail -n500 ' . ini_get('error_log'));
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'clearPhpErrors':
                    File::delete(ini_get('error_log'));
                    $message = 'Cleared.';

                    break;

                case 'showLaravelErrorsAll':
                    $output = shell_exec('tail -n1000 ' . base_path() . '/storage/logs/laravel.log');
                    $message = "<pre id='showLaravelErrors'>" . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'showLaravelErrorsMain':
                    $output = shell_exec('tail -n1000 ' . base_path() . '/storage/logs/laravel-errors-main.log');
                    $message = "<pre id='showLaravelErrors'>" . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'clearLaravelErrorsAll':
                    File::delete(base_path() . '/storage/logs/laravel.log');
                    $message = 'Cleared.';

                    break;

                case 'clearLaravelErrorsMain':
                    File::delete(base_path() . '/storage/logs/laravel-errors-main.log');
                    $message = 'Cleared.';

                    break;

                case 'showNotFoundErrors':
                    $output = shell_exec('tail -n1000 ' . base_path() . '/storage/logs/not-found-errors.log');
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'clearNotFoundErrors':
                    File::delete(base_path() . '/storage/logs/not-found-errors.log');
                    $message = 'Cleared.';

                    break;

                case 'showNotFoundSecureErrors':
                    $output = shell_exec('tail -n1000 ' . base_path() . '/storage/logs/not-found-errors-secure.log');
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'clearNotFoundSecureErrors':
                    File::delete(base_path() . '/storage/logs/not-found-errors-secure.log');
                    $message = 'Cleared.';

                    break;

                case 'showBookingLog':
                    $output = shell_exec('tail -n500 ' . config('custom.userRoot') . '/logs/booking_log');
                    $message = '<pre>' . htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre>';

                    break;

                case 'clearBookingLog':
                    File::delete(config('custom.userRoot') . '/logs/booking_log');
                    $message = 'Cleared.';

                    break;

                case 'showServerErrors':
                    $output = shell_exec('tail -n500 /var/log/virtualmin/hostelz.com_error_log' . (App::environment() != 'production' ? '-dev' : ''));
                    $message = '<pre>' . htmlspecialchars($output) . '</pre>';

                    break;

                case 'clearServerErrors':
                    File::delete('/var/log/virtualmin/hostelz.com_error_log' . (App::environment() != 'production' ? '-dev' : ''));
                    $message = 'Cleared.';

                    break;

                case 'showSlowQueries':
                    $output = file_get_contents('/var/log/mysql/mysql-slow.log');
                    $message = '<pre>' . htmlspecialchars($output) . '</pre>';

                    break;

                case 'clearSlowQueries':
                    File::delete('/var/log/mysql/mysql-slow.log');
                    $message = 'Cleared.';

                    break;

                case 'showDiscUsage':
                    $message = view('staff.development.diskUsage', compact('diskInfo'));

                    break;

                case 'apiDocumentation':
                    $message = view('staff.development.apiDocumentation');

                    break;

                case 'phpInfo':
                    phpinfo();

                    break;
            }
        }

        return view('staff/index')->with('message', $message)->with('diskInfo', $diskInfo);
    }

    public function imported($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('hostelID_selectorIdFind'), Request::input('hostelID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'Imported',
            Imported::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'insertForm', 'insert', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['system', 'status', 'name', 'address1', 'city', 'hostelID'];
        $formHandler->listSort['name'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';

        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'createListing':
                    if ($formHandler->model->hostelID) {
                        $message = 'Error: A <a href="' . routeURL('staff-listings', $formHandler->model->hostelID) . '">listing</a> was already created.';
                    } else {
                        $listing = $formHandler->model->createListing();
                        $message = 'Created <a href="' . routeURL('staff-listings', $listing->id) . '">New Listing</a>';
                    }

                    break;
                case 'forceUpdateImportedPics':
                    $message = 'Pics updated';
                    break;
            }
        }

        return $formHandler->display('staff/edit-imported', compact('message'));
    }

    public function importedImport($systemName = '')
    {
        $message = '<p>Takes too long to safely run from a web browser. Use SSH to run:</p>' .
            '<p>cd /home/hostelz/production; php artisan hostelz:importedImport --testRun</p>' .
            '<p>php artisan hostelz:importedImport</p>' .
            '(Booking.com prefers imports be run after 5pm EST.)';

        return $message;
    }

    public function importedNameChanges()
    {
        switch (Request::input('command')) {
            case 'rename':
                $listing = Listing::findOrFail(Request::input('listingID'));
                $listing->updateAndLogEvent(['name' => Request::input('newName')], true, 'importedNameChanges', 'staff');
                Imported::where('id', Request::input('importedID'))->update(['previousName' => DB::raw('name')]); // also update the imported previousName

                return 'ok';

            case 'ignore':
                Imported::where('id', Request::input('importedID'))->update(['previousName' => DB::raw('name')]);

                return 'ok';

            case 'unlink':
                $imported = Imported::findOrFail(Request::input('importedID'));
                $imported->updateAndLogEvent(['hostelID' => 0], true, 'importedNameChanges', 'staff');

                return 'ok';

            case 'markAllAsCurrent':
                Imported::where('previousName', '!=', DB::raw('name'))->update(['previousName' => DB::raw('name')]);

                break;
        }

        $importeds = Imported::where('name', '!=', DB::raw('previousName'))
            ->where('previousName', '!=', '')
            ->where('hostelID', '!=', 0)
            ->where('status', Imported::STATUS_ACTIVE)
            ->groupBy('hostelID', 'name')
            ->orderBy('name')
            ->with('listing')
            ->lazy()
            ->filter(function ($imported) {
                $listing = $imported->listing;
                $ignoredWords = [$listing->address, $listing->city, $listing->cityAlt, $listing->region, $listing->country];

                if ($this->listingNamesAreSimilar($imported->name, $listing->name, $ignoredWords)) {
                    return false;
                }

                if ($listing->verified === Listing::$statusOptions['imported']) {
                    // The listing is 'imported' but not yet live, so just go ahead and rename it
                    $listing->name = $imported->name;
                    $listing->save();
                    $imported->previousName = $imported->name;
                    $imported->save();
                }

                return true;
            });

        return view('staff/importedNameChanges', compact('importeds'));
    }

    private function listingNamesAreSimilar($a, $b, $ignoredWords)
    {
        /*
        the change is not only adding a city name, region name or street name
        */

        if ($a === $b) {
            return true;
        }

        // to ignore accented characters, etc.
        $a = utf8ToAscii($a);
        $b = utf8ToAscii($b);

        // the change is more from small letters to capital letters
        $a = strtolower($a);
        $b = strtolower($b);

        // Characters to eliminate entirely
        $ignoredCharacters = [
            '"' . "'",
        ];
        $a = str_replace($ignoredCharacters, '', $a);
        $b = str_replace($ignoredCharacters, '', $b);

        // Phrases that are more than one word long (careful, it doesn't that there are actually spaces before/after these phrases)
        $ignoredPhrases = [
            'bed and breakfast', 'bed & breakfast', '-',
        ];
        $a = str_replace($ignoredPhrases, ' ', $a);
        $b = str_replace($ignoredPhrases, ' ', $b);

        $wordsA = explode(' ', $a);
        $wordsB = explode(' ', $b);

        $filterUnimportantWords = function ($word) use ($ignoredWords) {
            // the change is not only a symbol for instance '
            $word = trim($word, ',\'"');

            // the change is more than only added a comma or dash
            // the change is not only removing or adding the word "hostel" or "hotel"
            if (in_array($word, ['hostel', 'hotel', 'b&b', 'bnb', 'bednbreakfast',
                'casa', 'hostal', 'apartment', 'homestay', 'the',
                'backpackers', 'house', 'guesthouse', 'accommodation', ])) {
                return '';
            }

            if (in_array($word, $ignoredWords)) {
                return '';
            }

            // change is not "and" to "&" and vice versa
            if ($word == '&') {
                $word = 'and';
            }

            return $word;
        };

        $wordsA = array_filter(array_map($filterUnimportantWords, $wordsA));
        $wordsB = array_filter(array_map($filterUnimportantWords, $wordsB));

        // The change of the name is more than switching the words (e.g. "tomato hostel" to "hostel tomato")

        sort($wordsA);
        sort($wordsB);

        // Is considered similar if either words are a subset of the other
        if (! array_diff($wordsA, $wordsB)) {
            return true;
        }
        if (! array_diff($wordsB, $wordsA)) {
            return true;
        }

        return $wordsA === $wordsB;
    }

    public function bookings($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('userID_selectorIdFind'), Request::input('userID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('listingID_selectorIdFind'), Request::input('listingID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'Booking',
            Booking::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['bookingTime', 'status', 'listingID', 'userID', 'system', 'origination'];
        if (auth()->user()->hasPermission('admin')) {
            $formHandler->listSelectFields = array_merge($formHandler->listSelectFields, ['commission']);
        }
        $formHandler->listSort['bookingTime'] = 'desc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';
        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'resendConfirmationEmail':
                    /*
                    if (resendBookingConfirmationEmail($qf->data['id'])) $smarty->assign('resentConfirmationEmail', true);
                    */
                    break;

                case 'cancelBooking':
                    $systemClassName = $formHandler->model->getImportSystem()->getSystemService();
                    $result = $systemClassName::cancelBooking($formHandler->model, $message);
                    if ($message != '') {
                        $message = '<p>"' . $message . '"</p>';
                    }
                    $message .= ($result ? 'Booking canceled.' : 'Unable to cancel the booking.');

                    break;
            }
        }

        return $formHandler->display('staff/edit-bookings', compact('message'))
            ->with('commissionTotal', $formHandler->mode == 'searchAndList' ? $formHandler->query->sum('commission') : 0);
    }

    public function users($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('mgmtListings_selectorIdFind'), Request::input('mgmtListings_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'User',
            User::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = (auth()->user()->hasPermission('admin') ? ['username', 'name', 'access'] : ['username', 'name']);
        $formHandler->listSort['id'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';

        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'balanceAdjustAmount':
                    $balanceAdjustAmount = Request::input('amount');
                    $reason = Request::input('reason');
                    if (! auth()->user()->hasPermission('admin') || ! $balanceAdjustAmount) {
                        break;
                    }
                    $formHandler->model->makeBalanceAdjustment($balanceAdjustAmount, $reason);
                    $message = 'Balance adjustment of $' . $balanceAdjustAmount . " (\"$reason\") recorded.";

                    break;
            }
        }

        return $formHandler->display('staff/edit-users', compact('message'));
    }

    public function userPay($userID)
    {
        $user = User::findOrFail($userID);

        $pastPayments = EventLog::where('userID', $userID)->where('action', 'payment')->orderBy('eventTime', 'desc')->get();

        $amountDue = Payments::calculateUserPay($user, $payDetails);

        $status = null;
        $paymentSystemBalance = null;

        if (Request::has('paypalPassword')) {
            // Pay Now
            if ($this->payStaffUser($user, $amountDue, $payDetails, Request::input('paypalPassword'))) {
                $status = 'payment sent';
            } else {
                $status = 'payment error';
            }

            $paymentSystemBalance = Payments::paymentSystemBalance(Request::input('paypalPassword'));
        }

        return view('staff/user-pay', compact('status', 'user', 'pastPayments', 'payDetails', 'amountDue', 'paymentSystemBalance'));
    }

    public function payAllUsers()
    {
        $staffUsers = User::where('status', 'ok')->where('paymentEmail', '!=', 'none')->
        havePermission(['staff', 'staffWriter', 'placeDescriptionWriter', 'reviewer', 'affiliate'])->get();

        $payments = [];

        DB::disableQueryLog(); // to save memory

        foreach ($staffUsers as $user) {
            if ($user->hasPermission('admin')) {
                continue;
            } // admin users don't get paid
            set_time_limit(60); // make sure there's enough time
            $amountDue = Payments::calculateUserPay($user, $payDetails);
            if ($amountDue < Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT) {
                continue;
            }

            $payment = [
                'user' => $user,
                'lastPaid' => (string) $user->lastPaid, // we store it separately here because the date will change when we make the payment below
                'amountDue' => $amountDue,
            ];

            if (Request::has('paypalPassword')) {
                // Pay Now
                if ($this->payStaffUser($user, $amountDue, $payDetails, Request::input('paypalPassword'))) {
                    $payment['status'] = 'payment sent';
                } else {
                    $payment['status'] = 'payment error';
                }
            }

            $payments[] = $payment;
        }

        if (Request::has('paypalPassword')) {
            $paymentSystemBalance = Payments::paymentSystemBalance(Request::input('paypalPassword'));
        } else {
            $paymentSystemBalance = null;
        }

        return view('staff/payAllUsers', compact('payments', 'paymentSystemBalance'));
    }

    private function payStaffUser($user, $amount, $logData, $paymentSystemPassword)
    {
        return $user->sendPayment($amount, 'Contractor', 'Hostelz.com Contractor Payment', $logData, 'staff', $paymentSystemPassword);
    }

    public function userSettings($userID, $action)
    {
        $handler = new UserSettingsHandler($userID, $action, 'staff/edit-users-settings', routeURL('staff-users', $userID));
        $handler->relaxedValidation = true; // So some fields aren't required, etc.

        return $handler->go();
    }

    public function placeDescriptionEditingInstructions()
    {
        return view('staff/placeDescriptionEditing-instructions');
    }

    // This is for SEO descriptions for listings that are specific to the cheap/best/party hostel pages.

    public function listingSpecialText($pathParameters = null)
    {
        if ($pathParameters === 'edit-or-create') {
            // This is a special URL we use that redirect to either create or edit depending on whether the text already exists yet.
            $existing = AttachedText::query()
                ->where('subjectType', 'hostels')
                ->where('subjectID', Request::input('listingID'))
                ->where('type', Request::input('type'))
                ->first();

            if ($existing) {
                return to_route('staff-listingSpecialText', $existing->id);
            }

            return Redirect::to(routeURL('staff-listingSpecialText', 'new') . '?' .
                http_build_query([
                    'data' => [
                        'subjectType' => 'hostels',
                        'subjectID' => Request::input('listingID'),
                        'type' => Request::input('type'),
                    ],
                ]));
        }

        $fieldInfo = [
            'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
            'language' => [
                'type' => 'select',
                'options' => collect(Languages::allLiveSiteCodesKeyedByName())->filter(fn ($lang) => $lang === 'en'),
                'showBlankOption' => false,
                'optionsDisplay' => 'keys',
                'validation' => 'required',
            ],
            'subjectType' => ['type' => 'hidden'],
            'subjectID' => ['type' => '', 'fieldLabelText' => 'Listing ID'],
            'subjectString' => ['type' => 'hidden'],
            'type' => ['type' => 'hidden'],
            'data' => ['type' => 'textarea', 'rows' => 10, 'fieldLabelText' => 'Text'],
        ];

        $formHandler = new FormHandler(
            'AttachedText',
            $fieldInfo,
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'insertForm', 'insert', 'updateForm', 'update', 'delete'];

        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['subjectString', 'language'];
        $formHandler->listSort['id'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        return $formHandler->display('staff/edit-listingSpecialText');
    }

    public function languageStrings($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('userID_selectorIdFind'),
            Request::input('userID_selectorSearch'),
            User::havePermission('staffTranslation')
        );
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'LanguageString',
            LanguageString::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['language', 'group', 'key'];
        $formHandler->listSort['key'] = 'asc';

        return $formHandler->go('staff/edit-languageStrings', null, 'searchAndList');
    }

    public function eventLog($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('userID_selectorIdFind'), Request::input('userID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $fieldInfo = EventLog::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit');
        // (Have to add this ourselves because EventLog doesn't know how to use our User model.
        $fieldInfo['userID'] = [
            'dataType' => 'Lib\dataTypes\NumericDataType',
            'getValue' => function ($formHandler, $model) {
                return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
            }, ];

        $formHandler = new FormHandler('EventLog', $fieldInfo, $pathParameters, 'App\Helpers');
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'searchAndList', 'updateForm', 'update', 'delete', 'multiDelete'] :
            ['searchForm', 'searchAndList', 'display'];
        $formHandler->showFieldnameIfMissingLang = true;
        $formHandler->listSort['eventTime'] = 'desc';
        $formHandler->listPaginateItems = 150;
        $listFields = array_diff(
            ['eventTime', 'category', 'action', 'subjectType', 'subjectID', 'subjectString', 'data', 'userID'],
            Request::input('where', []) // exclude any where[] search parameters from the results list
        );
        if (Request::input('command') == 'analysis') {
            $formHandler->listDisplayFields = $listFields;
        } // because in analysis mode we need to whole object so timestamps are Carbon
        else {
            $formHandler->listSelectFields = $listFields;
        }

        $formHandler->go(null, null, 'searchAndList');

        $message = '';

        switch (Request::input('command')) {
            case 'analysis':
                $userID = Request::has('search.userID') ? Request::input('search.userID') : Request::input('where.userID');
                if (! $userID) {
                    throw new Exception('Unknown user ID.  Must search by user ID.');
                }
                $user = User::find(Request::input('search.userID'));

                // Analyize the log entries a display the user's income and estimated income per hour.
                $list = $formHandler->list->reverse(); // we need the events in ascending order

                $MAX_DELAY_SECONDS = 12 * 60;
                $sessionStartTimestamp = 0;
                $lastEventTimestamp = 0;
                $sessionLogIDs = [];
                foreach ($list as $log) {
                    $eventTimestamp = $log->eventTime->getTimestamp();
                    $diffInSeconds = $eventTimestamp - $lastEventTimestamp;
                    if ($lastEventTimestamp && ($diffInSeconds > $MAX_DELAY_SECONDS || $log->id == $list->last()->id)) {
                        $sessionSeconds = $lastEventTimestamp - $sessionStartTimestamp;
                        if ($sessionSeconds == 0 || count($sessionLogIDs) < 5) {
                            $message .= '<b>Short session ignored.</b><br><br>';
                        } // single-event session
                        else {
                            $sessionPay = Payments::calculateUserPay($user, $outputIgnored, $sessionLogIDs);
                            $message .= '<b>End of Session: ' . round($sessionSeconds / 60) . ' min. - ' . count($sessionLogIDs) .
                                " actions - \$$sessionPay -> \$" . round($sessionPay * (3600 / $sessionSeconds), 2) . '/hour</b><br><br>';
                        }
                        $sessionStartTimestamp = $eventTimestamp;
                        $sessionLogIDs = [];
                    }
                    if (! $sessionStartTimestamp) {
                        $sessionStartTimestamp = $eventTimestamp;
                    }

                    $message .= "$eventTimestamp $log->eventTime $log->action $log->subjectType $log->subjectID<br>";
                    $sessionLogIDs[] = $log->id;
                    $lastEventTimestamp = $eventTimestamp;
                }

                break;
        }

        $extraListFields = null;
        if (auth()->user()->hasPermission('admin') && ($formHandler->mode == 'list' || $formHandler->mode == 'searchAndList')) {
            $extraListFields = [];
            foreach ($formHandler->list as $rowKey => $rowItem) {
                $extraListFields[$rowKey] = [
                    $rowItem->subjectEditFormURL() == '' ? '' :
                        '<a href="' . $rowItem->subjectEditFormURL() . '">view subject</a>',
                ];
            }
        }

        return $formHandler->display('staff/edit-eventLog', compact('message', 'extraListFields'));
    }

    public function ratings($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('userID_selectorIdFind'), Request::input('userID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('hostelID_selectorIdFind'), Request::input('hostelID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'Rating',
            Rating::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['hostelID', 'commentDate', 'name', 'status', 'notes'];
        $formHandler->listSort['commentDate'] = 'desc';
        $formHandler->listSort['name'] = 'desc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';
        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'spamicityEvaluate':
                    /*
                    require 'comments.inc.php';
                    bayesianEvaluateComment($_REQUEST['id'], true, true);
                    */
                    break;

                case 'emailToHostel':
                    /*
                    $qf->mode = 'update';
                	$qf->dbFetchAfterUpdate = true;
                	$qf->data['status'] = 'removed'
                	$qf->data['notes'] = trim('(emailed to hostel) '.$qf->data['notes']);

                	$email = dbGetOne("SELECT supportEmail FROM listings WHERE id=".dbEscapeInt($qf->data['hostelID']));
                    if ($email == '') {
                        echo "No support email address.";
                        exit();
                    }
                    else {
                        mail($email, $qf->data['summary'], $qf->data['comment'], "From: ".$qf->data['email'], "-f".$qf->data['email']);
                    }
                	*/
                    break;
            }
        }

        if ($formHandler->mode == 'update') {
            $rating = $formHandler->model;
            // easier to recalculate than it is to figure out whether this rating was already approved, is getting removed, etc.
            if ($rating->user) {
                $rating->user->recalculatePoints();
            }
        }

        return $formHandler->display('staff/edit-ratings', compact('message'));
    }

    public function pics($pathParameters = null)
    {
        $formHandler = new FormHandler(
            'Pic',
            Pic::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['status', 'subjectType', 'subjectID', 'type', 'source', 'picNum'];
        $formHandler->listSort = ['subjectType' => 'asc', 'subjectID' => 'asc', 'picNum' => 'asc'];
        $formHandler->go(null, null, 'searchAndList');

        $extraListFields = [];
        if ($formHandler->mode == 'list' || $formHandler->mode == 'searchAndList') {
            foreach ($formHandler->list as $rowKey => $rowItem) {
                $extraListFields[$rowKey] = ['<img style="height: 120px" src="' . $rowItem->url(['thumbnail', 'thumbnails', 'big', '', 'originals']) . '">'];
            }
        }

        return $formHandler->display('staff/edit-pics', compact('extraListFields'));
    }

    public function ads($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('userID_selectorIdFind'),
            Request::input('userID_selectorSearch'),
            User::havePermission('staffMarketingLevel2')
        );
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleIncomingLinkSearch(Request::input('incomingLinkID_selectorIdFind'), Request::input('incomingLinkID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $placeSearchResult = Ad::handlePlaceSearchAjaxCommand();
        if ($placeSearchResult !== null) {
            return $placeSearchResult;
        }

        $formHandler = new FormHandler(
            'Ad',
            Ad::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes =
            ['searchForm', 'list', 'searchAndList', 'display', 'insertForm', 'insert', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['status', 'name', 'linkURL', 'placementType', 'incomingLinkID'];
        $formHandler->listSort['id'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';

        if ($formHandler->mode == 'updateForm') {
            $ad = $formHandler->model;

            $adsForTheSamePlace = Ad::where('id', '!=', $ad->id)->samePlaceAs($ad)->get();

            if (Request::has('objectCommand')) {
                switch (Request::input('objectCommand')) {
                    case 'duplicate':
                        $duplicate = $ad->duplicate();
                        $message = 'Duplicate created. <a href="' . routeURL(Route::currentRouteName(), $duplicate->id) . '">View New Ad</a>';
                }
            }

            // FileList
            $pics = $ad->pics;
            $fileList = new FileListHandler($pics, null, null, true);
            $fileList->picListSizeTypeNames = ['', 'originals'];
            if (auth()->user()->hasPermission('staffPicEdit')) {
                $fileList->viewLinkClosure = function ($row) {
                    return routeURL('staff-pics', [$row->id, 'pics']);
                };
            }
            $response = $fileList->go();
            if ($response !== null) {
                return $response;
            }

            // FileUpload
            $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], false, false, count($pics));
            $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($ad): void {
                $ad->addPic($filePath);
            });
            if ($response !== null) {
                return $response;
            }
        } else {
            $fileUpload = $fileList = $adsForTheSamePlace = null;
        }

        return $formHandler->display('staff/edit-ads', compact('fileList', 'fileUpload', 'message', 'adsForTheSamePlace'));
    }

    public function articles($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('userID_selectorIdFind'), Request::input('userID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'Article',
            Article::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'insertForm', 'insert', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['userID', 'title', 'status', 'placementType', 'placement', 'submitDate', 'newUserComment'];
        $formHandler->listSort['id'] = 'asc';

        $formHandler->go(null, null, 'searchAndList');

        $message = '';
        $fileUpload = $fileList = null;

        $article = $formHandler->model; // for convenience

        if ($formHandler->mode === 'updateForm') {
            if ($article->newUserComment) {
                $article->newUserComment = false;
                $article->save();
            }

            if (Request::has('objectCommand')) {
                // objectCommands are commands performed on the object after it has been loaded
                switch (Request::input('objectCommand')) {
                    case 'preview':
                        return view('articles.article', compact('article'))->with('articleText', $article->getArticleTextForDisplay())->with('articles', []);
                }
            }

            // FileList
            $pics = $article->pics;
            $fileList = new FileListHandler($pics, ['caption'], ['caption'], true);
            $fileList->useIsPrimary = true;
            $fileList->picListSizeTypeNames = ['originals'];
            if (auth()->user()->hasPermission('staffPicEdit')) {
                $fileList->viewLinkClosure = function ($row) {
                    return routeURL('staff-pics', [$row->id, 'pics']);
                };
            }
            $response = $fileList->go();
            if ($response !== null) {
                return $response;
            }

            // FileUpload
            $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], false, false, count($pics));
            $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($article): void {
                $article->addPic($filePath);
                $article->clearRelatedPageCaches();
            });
            if ($response !== null) {
                return $response;
            }
        } elseif ($formHandler->mode === 'update') {
            if ($article->status === 'accepted' && $article->payStatus === '' &&
                ! EventLog::where('subjectID', $article->id)->where('subjectType', 'Article')->where('action', 'accepted')->exists()) {
                $article->payStatus = 'paid';
                $article->save();
                EventLog::log(
                    'staff',
                    'accepted',
                    'Article',
                    $article->id,
                    wordCount($article->finalArticle != '' ? $article->finalArticle : $article->originalArticle),
                    '',
                    $article->userID
                );
            }
        }

        return $formHandler->display('staff/edit-articles', compact('message', 'fileList', 'fileUpload'));
    }

    public function hostelgeeks($pathParameters = null)
    {
        $fields = Listing::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit');
        $fields['emailsAll'] = [
            'getValue' => function ($formHandler, $model) {
                return collect([$model->supportEmail, $model->bookingsEmail, $model->importedEmail, $model->managerEmail])
                    ->flatten()
                    ->unique()
                    ->implode('<br />');
            },
            'searchQuery' => function ($formHandler, $query, $value) {
                return $query->where(function (Builder $query) use ($value) {
                    $query->where('supportEmail', $value)
                        ->orWhere('bookingsEmail', $value)
                        ->orWhere('importedEmail', $value)
                        ->orWhere('managerEmail', $value);
                });
            },
            'orderBy' => function ($formHandler, Builder $query, $sortDirection) {
                return $query->orderBy('supportEmail', $sortDirection)
                    ->orderBy('managerEmail', $sortDirection)
                    ->orderBy('importedEmail', $sortDirection)
                    ->orderBy('bookingsEmail', $sortDirection);
            },
        ];
        $formHandler = new FormHandler(
            'Listing',
            $fields,
            $pathParameters,
            'App\Models\Listing'
        );
        $formHandler->query = Listing::featuredOnHostelgeeks();
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['verified', 'name', 'emailsAll', 'combinedRating', 'city', 'country', 'comment', 'featured'];

        $formHandler->fieldInfo['featured']['fieldLabelText'] = 'Featured';
        $formHandler->fieldInfo['combinedRating']['fieldLabelText'] = 'Rating';
        $formHandler->fieldInfo['combinedRating']['getValue'] = function ($formHandler, $model) {
            return $model->combinedRating / 10;
        };
        $formHandler->fieldInfo['verified']['options'] = array_flip(__('Listing.isLiveOrWhyNot'));
        $formHandler->fieldInfo['verified']['optionsDisplay'] = 'keys';
        $formHandler->fieldInfo['verified']['getValue'] = function ($formHandler, $model) {
            return array_search($model->verifiedOption, __('Listing.isLiveOrWhyNot'), true);
        };

        $formHandler->listSort['name'] = 'asc';
        $formHandler->go(null, $formHandler->allowedModes, 'searchAndList');

        $title = 'Hostelgeeks';

        return $formHandler->display('staff/edit-hostelgeeks', compact('title'));
    }

    public function reviews($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('reviewerID_selectorIdFind'),
            Request::input('reviewerID_selectorSearch'),
            User::havePermission('reviewer')
        );
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('hostelID_selectorIdFind'), Request::input('hostelID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'Review',
            Review::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = auth()->user()->hasPermission('admin') ?
            ['status', 'reviewerID', 'hostelID', 'newReviewerComment'] :
            ['status', 'hostelID'];
        $formHandler->listSort['reviewerID'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';

        if ($formHandler->mode == 'updateForm') {
            $review = $formHandler->model; // for convenience

            if ($review->newReviewerComment) {
                $review->newReviewerComment = false;
                $review->save();
            }

            // FileList
            $pics = $formHandler->model->pics;
            $fileList = new FileListHandler($pics, ['caption'], ['caption'], true);
            $fileList->picListSizeTypeNames = ['thumbnail'];
            $fileList->makeSortableUsingNumberField = 'picNum';
            $fileList->allowDelete = auth()->user()->hasPermission('staffPicEdit'); // (or we could allow our review editor to delete photos)
            if (auth()->user()->hasPermission('staffPicEdit')) {
                $fileList->viewLinkClosure = function ($row) {
                    return routeURL('staff-pics', [$row->id, 'pics']);
                };
            }
            $response = $fileList->go();
            if ($response !== null) {
                return $response;
            }

            // Macros
            // (items added here should also be documented in views/staff/edit-macros.blade.php.)
            $macroReplacementStrings = [
                '[listingCorrectionForm]' => routeURL('listingCorrection', $formHandler->model->hostelID, 'publicSite'),
                '[minimumWordsAccepted]' => Review::$minimumWordsAccepted,
                '[minimumPicWidthAccepted]' => Review::NEW_PIC_MIN_WIDTH, '[minimumPicHeightAccepted]' => Review::NEW_PIC_MIN_HEIGHT,
            ];
            $macros = Macro::getMacrosTextArray('review', auth()->user(), [], $macroReplacementStrings);
        } else {
            $fileList = $macros = null;
        }

        if ($formHandler->mode == 'update' && $formHandler->model->status == 'postAsRating') {
            // This might get done multiple times if it was already of the postAsRating status, but that's ok.
            $formHandler->model->publishAsARating();
            $message = 'Published as a rating.';
        }

        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded
            $review = $formHandler->model;

            switch (Request::input('objectCommand')) {
                case 'doPlagiarismChecks':
                    $review->doPlagiarismChecks(true);
                    $message = '<pre>Plagiarism check information updated.</pre><br>';
                    $formHandler->model = $formHandler->model->fresh(); // reload it in case anything changed.

                    break;
            }
        }

        switch (Request::input('command')) {
            case 'logPaymentsForAcceptedReviews':
                $message = '<h3>Log Payments for Accepted Reviews</h3>' . Review::logPaymentsForAcceptedReviews();

                break;
        }

        return $formHandler->display('staff/edit-reviews', compact('fileList', 'macros', 'message'));
    }

    public function geonames($pathParameters = null)
    {
        $formHandler = new FormHandler(
            'Geonames',
            Geonames::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->showFieldnameIfMissingLang = true;
        $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'display'];
        $formHandler->listPaginateItems = 100;
        // $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['name', 'featureClass', 'featureCode', 'countryCode'];
        $formHandler->listSort['name'] = 'asc';

        return $formHandler->go('staff/edit-geonames', null, 'searchAndList');
    }

    public function cityInfoPics($cityID)
    {
        $cityInfo = CityInfo::findOrFail($cityID);
        $existingPics = $cityInfo->pics;

        // FileList

        $fileList = new FileListHandler($existingPics, ['caption'], ['caption'], true);
        $fileList->useIsPrimary = true;
        $fileList->makeSortableUsingNumberField = 'picNum';
        $fileList->picListSizeTypeNames = ['', 'originals'];
        $fileList->viewLinkClosure = function ($row) {
            return routeURL('staff-pics', $row->id);
        };
        $response = $fileList->go();
        if ($fileList->filesModified) {
            $cityInfo->clearRelatedPageCaches();
        }
        if ($response !== null) {
            return $response;
        }

        // FileUpload

        $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], false, false, count($existingPics));
        $fileUpload->minImageWidth = CityInfo::PIC_MIN_WIDTH;
        $fileUpload->minImageHeight = CityInfo::PIC_MIN_HEIGHT;
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($cityInfo): void {
            $cityInfo->addPic($filePath, auth()->id());
            $cityInfo->clearRelatedPageCaches();
        });
        if ($response !== null) {
            return $response;
        }

        return view('staff/edit-cityInfo-pics', compact('fileList', 'fileUpload', 'cityInfo'));
    }

    public function cityComments($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('userID_selectorIdFind'), Request::input('userID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleCityInfoSearch(Request::input('cityID_selectorIdFind'), Request::input('cityID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'CityComment',
            CityComment::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['cityID', 'commentDate', 'name', 'comment', 'status'];
        $formHandler->listSort['commentDate'] = 'desc';
        $formHandler->go(null, null, 'searchAndList');

        if ($formHandler->mode == 'update') {
            $cityComment = $formHandler->model;
            // easier to recalculate than it is to figure out whether this comment was already approved, is getting removed, etc.
            if ($cityComment->user) {
                $cityComment->user->recalculatePoints();
            }
        }

        return $formHandler->display('staff/edit-cityComments');
    }

    public function countryInfo($pathParameters = null)
    {
        $message = '';

        $formHandler = new FormHandler(
            'CountryInfo',
            CountryInfo::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['continent', 'country', 'regionType', 'cityCount'];
        $formHandler->listSort['country'] = 'asc';
        $formHandler->callbacks = [
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use (&$message) {
                // Remember renaming
                if ($data['rememberCountryRenaming']) {
                    // saveCorrection($dbTable, $dbField, $oldValue, $newValue, $contextValue1 = null, $contextValue2 = null, $returnInsertAsArray = false)
                    if ($formHandler->model->country != $data['country'] && $formHandler->model->country != '') {
                        DataCorrection::saveCorrection('', 'country', $formHandler->model->country, $data['country']);
                        // Also rename the country in other cityInfos and all listings in other cities
                        Listing::where('country', $formHandler->model->country)->update(['country' => $data['country']]);
                        CityInfo::where('country', $formHandler->model->country)->update(['country' => $data['country']]);
                        $message .= "<p>Remembering '" . $formHandler->model->country . "' -> '$data[country]'.</p>";
                    }
                }

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
        ];
        $formHandler->go(null, null, 'searchAndList');

        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'searchRank':
                    $rank = $formHandler->model->updateSearchRank();
                    $message = "Search rank updated ($rank).";

                    break;
            }
        }

        return $formHandler->display('staff/edit-countryInfo', compact('message'));
    }

    public function myMacros($pathParameters = null)
    {
        $formHandler = new FormHandler('Macro', Macro::fieldInfo('staffEdit'), $pathParameters, 'App\Models');
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['purpose', 'category', 'name', 'conditions'];
        $formHandler->listSort = ['purpose' => 'asc', 'category' => 'asc', 'name' => 'asc'];

        $formHandler->whereData = ['userID' => auth()->id()];
        $formHandler->forcedData['userID'] = auth()->id();

        return $formHandler->go('staff/edit-macros', null, 'searchAndList');
    }

    // (admin only macro editing)

    public function macros($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('userID_selectorIdFind'),
            Request::input('userID_selectorSearch'),
            User::havePermission('staff')
        );
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler('Macro', Macro::fieldInfo('adminEdit'), $pathParameters, 'App\Models');
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['userID', 'purpose', 'category', 'name', 'conditions'];
        $formHandler->listSort = ['purpose' => 'asc', 'category' => 'asc', 'name' => 'asc'];

        return $formHandler->go('staff/edit-macros', null, 'searchAndList');
    }

    public function savedLists($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('user_id_selectorIdFind'), Request::input('user_id_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'SavedList',
            SavedList::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['name', 'user_id'];
        $formHandler->listSort = ['user_id' => 'asc', 'name' => 'asc'];

        return $formHandler->go('staff/edit-savedLists', null, 'searchAndList');
    }

    public function searchRank($pathParameters = null)
    {
        $placeSearchResult = SearchRank::handlePlaceSearchAjaxCommand();
        if ($placeSearchResult !== null) {
            return $placeSearchResult;
        }

        $formHandler = new FormHandler(
            'SearchRank',
            SearchRank::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes =
            ['searchForm', 'list', 'searchAndList', 'display', 'insertForm', 'insert', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['checkDate', 'searchPhrase', 'placeSelector', 'rank'];
        $formHandler->listSort['checkDate'] = 'desc';
        $formHandler->go(null, null, 'searchAndList');

        $message = '';

        return $formHandler->display('staff/edit-searchRank', compact('message'));
    }

    /* Admin */

    public function dataCorrection($pathParameters = null)
    {
        $formHandler = new FormHandler(
            'DataCorrection',
            \Lib\DataCorrection::fieldInfo(),
            $pathParameters,
            'Lib'
        );
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['dbTable', 'dbField', 'contextValue1', 'contextValue2', 'oldValue', 'newValue'];
        $formHandler->listSort = ['dbTable' => 'asc', 'dbField' => 'asc', 'contextValue1' => 'asc', 'contextValue2' => 'asc',
            'oldValue' => 'asc', 'newValue' => 'asc', ];

        return $formHandler->go('staff/edit-dataCorrection', null, 'searchAndList');
    }

    public function dataCorrectionMass($table, $dbField)
    {
        switch ($table) {
            case 'listings':
                $actualDbTable = Listing::$staticTable;
                $query = DB::table($actualDbTable)->orderBy('country')->orderBy('region')->orderBy('city');

                break;

            default:
                App::abort(404);
        }

        switch ($dbField) {
            case 'country':
                $contextInfo = Listing::$dataCorrectionContexts[$dbField];
                $dbTable = ''; // these are shared between cityInfo and listings, so we just leave the table blank

                break;

            case 'city':
            case 'region':
                $contextInfo = Listing::$dataCorrectionContexts[$dbField];
                $dbTable = ''; // these are shared between cityInfo and listings, so we just leave the table blank

                break;

            case 'cityAlt': // this is the Listing table cityAlt (neighborhood), not the CityInfo cityAlt
                $contextInfo = Listing::$dataCorrectionContexts[$dbField];
                $dbTable = ''; // might be used by other tables, so just leave the dbTable blank.

                break;
        }

        $result = \Lib\DataCorrectionHandler::massDataCorrection(
            'staff/dataCorrection-mass',
            $dbTable,
            $dbField,
            routeURL('staff-listings'),
            $contextInfo[0],
            $contextInfo[1],
            $query,
            $actualDbTable,
            null,
            true
        );

        // We also correct the data in the cityInfo table... (we do this when the page if first loaded, and also after submitting new values)
        if ($table == 'listings' && $dbField != 'cityAlt') { // Not for cityAlt because the cityAlt is the Listing's neighborhood
            DataCorrection::correctAllDatabaseValues($dbTable, $dbField, CityInfo::query(), CityInfo::$staticTable, null, $contextInfo[0], $contextInfo[1]);
        }

        return $result;
    }

    public function userAutoLogin($userID)
    {
        auth()->loginUsingId($userID);

        return Redirect::route('user:menu');
    }

    public function translation($language, $group = '')
    {
        if ($group == '') {
            return view('staff/translation', ['language' => $language, 'groups' => LanguageString::getAllTranslationsNeededCounts($language)]);
        } else {
            if (Request::has('translations')) {
                LanguageString::updateTranslations($language, $group, Request::input('translations'), Request::input('originalEnglishText'), 'staff');

                return view('staff/translation', ['language' => $language, 'group' => $group, 'updated' => true]);
            }

            $strings = LanguageString::getStringsToTranslate($language, $group, $translationInfo);

            return view('staff/translation', ['language' => $language, 'group' => $group, 'strings' => $strings, 'translationInfo' => $translationInfo]);
        }
    }

    // Geneneral purpose translation.  Inputs: text, languageFrom, languageTo.

    public function translateText()
    {
        $translatedText = TranslationService::translate(Request::input('text'), Request::input('languageFrom') ?: null, Request::input('languageTo'), 2500, $detectedLanguageCode);
        $detectedLanguageName = ($detectedLanguageCode != '' ? App\Models\Languages::get($detectedLanguageCode)->name : $detectedLanguageCode);

        return response()->json(['translation' => $translatedText, 'detectedLanguageCode' => $detectedLanguageCode,
            'detectedLanguageName' => $detectedLanguageName, ]);
    }

    public function useGeocodingInfo()
    {
        $maxItems = 1000;

        $message = '';

        $comparisonTypeOptions = ['equals', 'substring', 'isEmpty', 'notEmpty'];

        $fieldInfo = [
            'verified' => ['type' => 'select', 'options' => Listing::$statusOptions, 'optionsDisplay' => 'translateKeys'],
            'name' => ['comparisonTypeOptions' => $comparisonTypeOptions],
            'continent' => ['comparisonTypeOptions' => $comparisonTypeOptions],
            'country' => ['comparisonTypeOptions' => $comparisonTypeOptions],
            'region' => ['comparisonTypeOptions' => $comparisonTypeOptions],
            'city' => ['comparisonTypeOptions' => $comparisonTypeOptions],
            'cityAlt' => ['comparisonTypeOptions' => $comparisonTypeOptions],
            'isLive' => ['type' => 'checkbox', 'value' => true, 'fieldLabelText' => '', 'checkboxText' => 'Listing is Live',
                'searchFormDefaultValue' => true, 'searchQuery' => function ($formHandler, $query, $value) {
                    return $value ? $query->areLive() : $query;
                }, ],
        ];

        $geocodingFields = ['country', 'region', 'area', 'area2', 'colloquialArea', 'city', 'cityArea', 'neighborhood'];

        // We use formHandler to allow searching by city/region/country/etc.
        $formHandler = new FormHandler('Listing', $fieldInfo, '', 'App\Models\Listing');
        $formHandler->defaultComparisonType = 'equals';
        $formHandler->query = Listing::areNotListingCorrection()->limit($maxItems); // have to limit the items because doesn't work properly with pagination
        $formHandler->listPaginateItems = $maxItems;
        $formHandler->listDisplayFields = [/* 'name', */
            'cityAlt', 'city', 'region', 'country'];
        $formHandler->listSort = ['cityAlt' => 'asc', 'city' => 'asc', 'region' => 'asc', 'country' => 'asc'];
        $formHandler->allowedModes = ['searchForm', 'list'];
        $formHandler->go(null, null, 'searchForm');

        $noGeocodingInfo = $geoInfo = [];

        if ($formHandler->mode == 'list') {
            // Get Geocoding Info
            $formHandler->list = $formHandler->list->filter(function (Listing $listing) use (&$noGeocodingInfo, &$geoInfo) {
                if ((! $listing->hasLatitudeAndLongitude()) ||
                    ! ($result = Geocoding::reverseGeocode($listing->latitude, $listing->longitude, Listing::LATLONG_PRECISION)) ||
                    $result['accuracy'] < Geocoding::$ACCURACY_TYPE['approxArea']) {
                    $noGeocodingInfo[] = $listing;

                    return false;
                }
                $geoInfo[$listing->id] = $result;

                return true;
            });
        }

        if (Request::has('setListingField')) {
            $listingField = Request::input('setListingField');
            $geocodingField = Request::input('toGeocodingField');
            $selectedListings = Request::input('multiSelect');

            if ($listingField == '' || $geocodingField == '') {
                $message = 'Missing to or from field.';
            } elseif (! $selectedListings) {
                $message = 'No listings selected.';
            } else {
                foreach ($selectedListings as $listingID) {
                    /** @var Listing $listing */
                    $listing = Listing::findOrFail($listingID);
                    if (! array_key_exists($listing->id, $geoInfo)) {
                        print_r($selectedListings);
                        print_r($geoInfo);

                        throw new Exception("Listing $listing->id not found in geoInfo.");
                    }
                    $newValue = $geoInfo[$listing->id][$geocodingField];
                    if ($newValue == '') {
                        continue;
                    }
                    $change = "$listingField: '" . $listing->$listingField . "' -> '$newValue'";
                    $correctedValue = DataCorrection::getCorrectedValue(
                        '',
                        $listingField,
                        $newValue,
                        $listing->getDataCorrectionContextValue($listingField, 1),
                        $listing->getDataCorrectionContextValue($listingField, 2)
                    );
                    if ($correctedValue != $newValue) {
                        $change .= " -> (DataCorrection) '$correctedValue'";
                        $newValue = $correctedValue;
                    }
                    $message .= '<div>"<a href="' . routeURL('staff-listings', $listing->id) . '">' . $listing->name . '</a>" ' . htmlentities($change) . '</div>';
                    $listing->updateAndLogEvent([$listingField => $newValue], true, 'useGeocodingInfo', 'staff');
                }
            }
        }

        return $formHandler->display('staff/useGeocodingInfo', compact('message', 'geocodingFields', 'geoInfo', 'noGeocodingInfo'));
    }

    public function taxReports(): void
    {
        // Note: This also changes CX to C so that transactions aren't already marked as reconciled.

        if ($paypalQIF = Request::input('paypalCSV')) {
            $lines = explode("\n", $paypalQIF);

            $headerLine = array_shift($lines);

            $delimiter = (Str::contains($headerLine, "\t") ? "\t" : ',');
            $headerFields = str_getcsv($headerLine, $delimiter);
            $headerFields = array_map('trim', $headerFields);

            $columnMap = array_flip($headerFields);

            $outputs = [];
            foreach ($lines as $line) {
                if (trim($line) == '') {
                    continue;
                }

                $lineData = str_getcsv($line, $delimiter);
                $record = array_combine(array_flip($columnMap), $lineData);
                $record = array_map('trim', $record);

                // Ignore
                $ignoreTransactions = [
                    'Reversal of General Account Hold',
                    'Hold on Available Balance',
                    'Bank Deposit to PP Account',
                ];
                if (in_array($record['Type'], $ignoreTransactions)) {
                    continue;
                }

                // Category

                $typeToCategoryMap = [
                    'Mass Pay Payment' => 'Bus. Exp.:Programming:Contract Labor:Hostelz',
                    'Subscription Payment' => 'Bus. Exp.:Programming:Utilities:Web Hosting',
                    'Website Payment' => '', // misc
                    'Payment Refund' => '',
                    'Mass Pay Reversal' => '',
                ];

                $category = $typeToCategoryMap[$record['Type']] ?? null;
                if ($category === null) {
                    echo "Unknown type '$record[Type]'.";
                    exit();
                }

                $outputs[] =
                    "!Type:Cash\n" .
                    'D' . $record['Date'] . "\n" .
                    'T' . $record['Net'] . "\n" .
                    'L' . $category . "\n" .
                    "C\n" .
                    'M' . $record['Type'] . "\n" .
                    'P' . $record['Name'] . "\n" .
                    "^\n";
            }

            echo '<pre>' . implode('', $outputs) . '</pre>';
        } elseif (Request::input('taxYear')) {
            echo '<table>';
            $sumByUserIDs = EventLog::where('action', 'payment')->where('eventTime', 'LIKE', Request::input('taxYear') . '-%')
                ->groupBy('userID')->select('userID', DB::raw('SUM(subjectString) as amountSum'))->get();
            foreach ($sumByUserIDs as $sumByUserID) {
                if ($sumByUserID->amountSum < 600) {
                    continue;
                } // Under $600 doesn't have to be reported to the IRS
                $user = User::findOrFail($sumByUserID->userID);
                echo "<tr><td>$user->name<td><a href=\"" . routeURL('staff-users', $user->id) . "\">$user->username</a><td>" . implode(', ', $user->access) . "<td>$user->homeCountry<td>\$$sumByUserID->amountSum";
            }
            echo '</table>';
        } else {
            echo '<form method=post>' . csrf_field() . 'CSV data from PayPal: <textarea name=paypalCSV></textarea><input type=submit></form>';
            echo '<p><form method=post>' . csrf_field() . 'Tax Year for Contractor Payment totals: <input name=taxYear><input type=submit></form>';
        }
    }

    /*
    private function outputQIF($records)
    {
        $map = [
            'type' => '!', 'Date' => 'D', 'Net' => 'T', 'Name' => 'P',
            'Type' => 'M'
        ];

        $output = '';

        foreach ($records as $record) {
            foreach ($record as $type => $value) {
                if ($type == 'categories') {
                    reset($value);
                    if (count($value) == 1) {
                        $output .= 'L' . key($value) . "\n";
                    } else {
                        foreach ($value as $category => $amount) {
                            $output .= 'S' . $category . "\n" . '$' . $amount . "\n";
                        }
                    }
                    continue;
                }
                $output .= $map[$type] . $value . "\n";
            }
            $output .= "^\n";
        }

        return $output;
    }
    */

    /* This worked with QIF format reports from PayPal, but PayPal no longer outputs QIF
    public function taxReports()
    {
        // Note: This also changes CX to C so that transactions aren't already marked as reconciled.

        if ($paypalQIF = Request::input('paypalQIF')) {
            $lines = explode("\n", $paypalQIF);

            $inputs = $this->parseQIF($lines);

            $outputs = [ ];
            foreach ($inputs as $record) {

                // Ignore

                $ignoreTransactions = [
                    'Reversal of General Account Hold',
                    'Hold on Available Balance'
                ];
                foreach ($ignoreTransactions as $cat) {
                    if (isset($record['categories'][$cat])) continue 2;
                }

                if (array_key_exists('Fee for Mass Pay request', $record['categories']) && $record['payee'] == '')
                    $record['payee'] = 'Fee for Mass Pay request';

                // Replace Category

                $replaceCategories = [
                    'Fee for Mass Pay request' => 'Bus. Exp.:Programming:Other:Bank Charges',
                    'Fee' => 'Bus. Exp.:Programming:Other:Bank Charges',
                    'General Currency Conversion' => 'Bus. Exp.:Programming:Other:Bank Charges',
                    'Bank Deposit to PP Account (Obselete)' => '',
                    'Bank Deposit to PP Account' => '',
                    'General Payment' => '',
                ];
                if (@$record['memo'] == 'Hostelz.com Contractor Payment')
                    $replaceCategories['Mass Pay Payment'] = 'Bus. Exp.:Programming:Contract Labor:Hostelz';
                if (@$record['payee'] == 'Open Planet Solutions LLC' ||
                        Str::startsWith(@$record['memo'], 'BigBlueHost'))
                    $replaceCategories['Subscription Payment'] = 'Bus. Exp.:Programming:Utilities:Web Hosting';
                if (@$record['memo'] == 'Fat Wallet Cash Back' ||
                        in_array($record['payee'], [ 'Ebates.com' ]))
                    $replaceCategories['Mass Pay Payment'] = 'Misc';
                if (in_array(@$record['memo'], [ 'Sponsored Article', 'Incoming Link', 'Paid Link Payment' ]))
                    $replaceCategories['Mass Pay Payment'] = 'Bus. Exp.:Programming:Advertising:Hostelz.com';

                foreach ($replaceCategories as $from => $to) {
                    if (isset($record['categories'][$from])) {
                        $record['categories'][$to] = $record['categories'][$from];
                        unset($record['categories'][$from]);
                    }
                }

                $record['cleared'] = '';

                $outputs[] = $record;
            }

            $acceptableCategories = [
                'Bus. Exp.:Programming:Utilities:Web Hosting',
                'Bus. Exp.:Programming:Other:Bank Charges',
                'Bus. Exp.:Programming:Contract Labor:Hostelz',
                'Bus. Exp.:Programming:Advertising:Hostelz.com',
                'Misc'
            ];
            foreach ($outputs as $output) {
                foreach ($output['categories'] as $category => $amount) {
                    if ($category != '' && !in_array($category, $acceptableCategories)) {
                        echo "<br><br>Unknown category '$category'.";
                        print_r($output);
                        continue 2;
                    }
                }
            }

            echo "<br><br>";

            $result = $this->outputQIF($outputs);

        	echo "<pre>$result</pre>";
        } elseif (Request::input('taxYear')) {
        	echo "<table>";
        	$sumByUserIDs = EventLog::where('action', 'payment')->where('eventTime', 'LIKE', Request::input('taxYear').'-%')
        	    ->groupBy('userID')->select('userID', DB::raw('SUM(subjectString) as amountSum'))->get();
        	foreach ($sumByUserIDs as $sumByUserID) {
        		if ($sumByUserID->amountSum < 600) continue; // Under $600 doesn't have to be reported to the IRS
        		$user = User::findOrFail($sumByUserID->userID);
        		echo "<tr><td>$user->name<td><a href=\"".routeURL('staff-users', $user->id)."\">$user->username</a><td>".implode(', ', $user->access)."<td>$user->homeCountry<td>\$$sumByUserID->amountSum";
        	}
        	echo "</table>";
        }
        else {
        	echo "<form method=post>".csrf_field()."QIF data from PayPal: <textarea name=paypalQIF></textarea><input type=submit></form>";
        	echo "<p><form method=post>".csrf_field()."Tax Year for Contractor Payment totals: <input name=taxYear><input type=submit></form>";
        }
    }


    //  * Will process a given QIF file. Will loop through the file and will send all transactions to the transactions API.
    //  * @param string $file
    //  * @param int $account_id
    private function parseQIF($lines)
    {
        $records = [ ];
        $record = [ ];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == '') continue;
            $firstChar = substr($line, 0, 1);
            $restOfLine = trim(substr($line, 1));

            switch ($firstChar) {
                case '^':
                    $records[] = $record;
                    $record = [ ];
                    $lastCategory = '';
                    break;

                case '!':
                    $record['type'] = $restOfLine;
                    // !Type:Cash
                    break;

                case 'D':
                    // Date. Leading zeroes on month and day can be skipped. Year can be either 4 digits or 2 digits or '6 (=2006).
                    $record['date'] = $restOfLine;
                    break;

                case 'T':
                    // Amount of the item. For payments, a leading minus sign is required.
                    $record['amount'] = $restOfLine;
                    break;

                case 'P':
                    // Payee. Or a description for deposits, transfers, etc.
                    // $line = htmlentities($line);
                    // $line = str_replace("  ", "", $line);
                    // $line = str_replace(array("&pound;","£"), 'GBP', $line);
                    $record['payee'] = $restOfLine;
                    break;

                case 'N':
                    // Investment Action (Buy, Sell, etc.).
                    $record['investment'] = $restOfLine;
                    break;

                case 'M':
                    $record['memo'] = $restOfLine;
                    break;

                case 'C':
                    $record['cleared'] = $restOfLine;
                    break;

                case 'L':
                    if (!isset($record['amount'])) throw Exception("I don't yet know how to handle cases where the category comes before the amount.");
                    if (!isset($record['categories'])) $record['categories'] = [ ];
                    $record['categories'][$restOfLine] = $record['amount'];
                    break;

                case 'S':
                    $lastCategory = $restOfLine; // we'll use this when we get the '$' amount below in the next line
                    break;

                case '$':
                    if ($restOfLine == 0.0) continue; // ignore $0 amount splits
                    if (!isset($record['categories'])) $record['categories'] = [ ];
                    $record['categories'][$lastCategory] = $restOfLine;
                    break;

                default:
                    echo "Unknown line '$line'.";
                    break;
            }
        }

        return $records;
    }

    */

    // Text for the cheap/best/party hostels pages.

    public function citySpecialText($pathParameters = null)
    {
        $fieldInfo = [
            'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
            'language' => [
                'type' => 'select',
                'options' => Languages::allLiveSiteCodesKeyedByName(),
                'showBlankOption' => 'false',
                'optionsDisplay' => 'keys',
                'validation' => 'required',
            ],
            'subjectType' => ['type' => 'hidden'],
            'subjectID' => ['type' => '', 'fieldLabelText' => 'City ID'],
            'subjectString' => ['type' => 'display', 'insertType' => 'text', 'fieldLabelText' => 'Position'],
            'type' => ['type' => 'hidden'],
            'data' => ['type' => 'WYSIWYG', 'rows' => 20, 'sanitize' => 'WYSIWYG', 'fieldLabelText' => 'Text'],
        ];

        $formHandler = new FormHandler(
            'AttachedText',
            $fieldInfo,
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'insertForm', 'insert', 'updateForm', 'update', 'delete'];

        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['subjectString', 'language'];
        $formHandler->listSort['id'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        return $formHandler->display('staff/edit-citySpecialText');
    }
}
