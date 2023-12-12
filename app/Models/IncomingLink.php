<?php

namespace App\Models;

use App\Models\Listing\Listing;
use App\Services\WebsiteStatusChecker;
use App\Traits\PlaceFields;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lib\BaseModel;
use Lib\LanguageDetection;
use Lib\Spider;
use Lib\WebsiteInfo;
use Lib\WebsiteTools;

class IncomingLink extends BaseModel
{
    use PlaceFields;

    public static $placeTypes = ['ContinentInfo', 'CountryInfo', 'Region', 'CityInfo', 'Listing'];

    protected $table = 'incomingLinks';

    public static $staticTable = 'incomingLinks'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $checkStatusOptions = ['ok', 'error'];

    public static $pageRankOptions = ['error' => -2, 'unknown' => -1, '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4,
        '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10, ];

    public static $otherWebsitesLinkedOptions = [
        'Hostelworld', 'Hostelworld Affiliate', 'HostelBookers', 'HostelBookers Affiliate', 'Hostels.com', 'HostelsClub', 'Gomio',
    ];

    public static $categoryOptions = ['accommodation', 'forum post', 'blog', 'tour', 'press', 'pressrelease', 'advertisement', 'guestPost',
        'web directory', 'info', 'edu', 'org', 'gov', 'other', 'unknown', ];

    public static $contactStatusOptions = ['todo', 'ignored', 'initialContact', 'discussing', 'closed', 'flagged'];

    public static $contactStatusSpecificOptions = [
        'ignored' => ['already', 'not relevant', 'error', 'foreign', 'spammy', 'paid', 'cant', 'ignore'], // Ignore reasons
        'closed' => ['noResponse', 'refused', 'maybe', 'agreed', 'verified', 'posted', 'declined', 'other'], // Closed reasons
    ];

    public static $contactTopicOptions = ['featuresList', 'affiliate', 'crossPromotion', /* 'reimburse', 'pay', 'ad', 'travelWriting', */
        'mediaAvailability',
        /* 'affiliteNonprofit', */ /* 'affiliate100',*/
        'provideArticle', ];

    public static $contactTopicsForCategory = [
        'info' => ['featuresList', 'affiliate', 'crossPromotion'],
        'blog' => ['featuresList', 'affiliate'],
        'tour' => ['crossPromotion'],
        'press' => ['featuresList', 'mediaAvailability'],
        'pressrelease' => ['featuresList', 'mediaAvailability'],
        'advertisement' => ['featuresList', 'affiliate', 'crossPromotion'],
        'web directory' => ['featuresList', 'affiliate', 'crossPromotion'],
        'info' => ['featuresList', 'crossPromotion'],
        'edu' => ['featuresList'],
        'org' => ['featuresList'],
        'gov' => ['featuresList'],
        'other' => ['featuresList', 'affiliate', 'crossPromotion'],
        'unknown' => ['featuresList', 'affiliate', 'crossPromotion'],
    ];

    public static $followUpStatusOptions = ['todo', 'done'];

    public static $mailingListOptions = ['pressReleases'];

    public static $allowMultipleURLsForDomains = ['wikitravel.org', 'couchwiki.org', /* 'wikipedia.org', (too many links with all the languages, etc.) */
        'wikivoyage.org', 'hitchwiki.org', 'wikia.com', 'webwiki.com', 'travel.com', 'wikimapia.org', 'wikinfo.org', 'wikitourist.org', 'wikitravel.org', 'wikivoyage.org', 'nytimes.com', 'todaytravel.today.com', 'blogs.forbes.com',
    ];

    public const INITIAL_FOLLOW_UP_AFTER_DAYS = 12;

    public function save(array $options = []): void
    {
        $this->contactEmails = array_unique(array_map('mb_strtolower', $this->contactEmails));

        parent::save($options);
    }

    /* Static */

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'otherWebsitesLinked' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'otherWebsitesLinked', self::$otherWebsitesLinkedOptions]),
                'contactEmails' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'contactEmails']),
                'invalidEmails' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'invalidEmails']),
                'contactTopics' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'contactTopics', self::$contactTopicOptions]),
                'mailingLists' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'mailingLists', self::$mailingListOptions]),
            ];
        }

        return $dataTypes;
    }

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'editType' => 'display'],
                    'url' => ['maxLength' => 500, 'validation' => 'url'],
                    'domain' => ['maxLength' => 100],
                    'contactStatus' => ['type' => 'select', 'options' => self::$contactStatusOptions, 'optionsDisplay' => 'translate'],
                    'contactStatusSpecific' => [],
                    'linksTo' => ['maxLength' => 500, 'validation' => 'url'],
                    'anchorText' => ['maxLength' => 250],
                    'followable' => ['type' => 'select', 'options' => ['n', 'y'], 'optionsDisplay' => 'translate'],
                    'source' => ['maxLength' => 500],
                    'placeID' => ['type' => 'ignore'], // just here so we can make URLs that search by place
                    'placeType' => ['type' => 'ignore'], // just here so we can make URLs that search by place
                    'placeSelector' => self::placeSelectorFieldInfo(),
                    'createDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType'],
                    'lastCheck' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'checkStatus' => ['type' => 'select', 'options' => self::$checkStatusOptions, 'optionsDisplay' => 'translate'],
                    'spiderResults' => ['type' => 'display', 'searchType' => 'text', 'getValue' => function ($formHandler, $model) {
                        return (string) $model->attributes['spiderResults']; // just output the json encoded string
                    }],
                    'pageTitle' => ['maxLength' => 500],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],

                    'pagerank' => ['type' => 'select', 'options' => self::$pageRankOptions, 'optionsDisplay' => 'translateKeys'],
                    'domainAuthority' => ['searchType' => 'minMax', 'type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'pageAuthority' => ['searchType' => 'minMax', 'type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'trafficRank' => ['searchType' => 'minMax', 'type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'otherWebsitesLinked' => ['dataTypeObject' => self::staticDataTypes()['otherWebsitesLinked'], 'type' => 'checkboxes',
                        'options' => self::$otherWebsitesLinkedOptions, 'optionsDisplay' => 'translate', ],
                    'category' => ['type' => 'select', 'options' => self::$categoryOptions, 'optionsDisplay' => 'translate'],
                    'name' => ['maxLength' => 100],
                    'priorityLevel' => ['searchType' => 'minMax', 'type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'contactEmails' => ['dataTypeObject' => self::staticDataTypes()['contactEmails'], 'editType' => 'multi', 'comparisonType' => 'substring', 'maxLength' => 500, 'validation' => 'emailList'],
                    'invalidEmails' => ['dataTypeObject' => self::staticDataTypes()['invalidEmails'], 'editType' => 'multi', 'comparisonType' => 'substring', 'maxLength' => 500, 'validation' => 'emailList'],
                    'contactFormURL' => ['maxLength' => 100],
                    'contactTopics' => ['dataTypeObject' => self::staticDataTypes()['contactTopics'], 'type' => 'checkboxes',
                        'options' => self::$contactTopicOptions, 'optionsDisplay' => 'translate', 'maxLength' => 500, ],
                    'lastContact' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'followUpStatus' => ['type' => 'select', 'options' => self::$followUpStatusOptions, 'optionsDisplay' => 'translate'],
                    'reminderDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'mailingLists' => ['dataTypeObject' => self::staticDataTypes()['mailingLists'], 'type' => 'checkboxes',
                        'options' => self::$mailingListOptions, 'optionsDisplay' => 'translate', 'maxLength' => 500, ],
                    'notes' => ['type' => 'textarea', 'rows' => 7],
                ];

                if ($purpose == 'staffEdit') {
                    $staffDisplayOnly = ['linksTo', 'anchorText', 'source', 'checkStatus', 'pageTitle', 'pagerank', 'lastContact'];
                    $staffIgnore = ['domain', 'lastCheck', 'spiderResults', 'priorityLevel', 'source', 'userID'];
                    foreach ($fieldInfos as $fieldName => $fieldInfo) {
                        if (in_array($fieldName, $staffDisplayOnly)) {
                            $fieldInfos[$fieldName]['editType'] = 'display';
                        }
                        if (in_array($fieldName, $staffIgnore)) {
                            $fieldInfos[$fieldName]['type'] = $fieldInfos[$fieldName]['searchType'] = $fieldInfos[$fieldName]['editType'] = 'ignore';
                        }
                    }
                }

                break;

            case 'contact':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'userID' => ['type' => 'ignore'], // just here so we can have URLs that search by userID
                    'url' => ['updateType' => 'ignore'],
                    'linksTo' => ['updateType' => 'display'],
                    'anchorText' => ['updateType' => 'display'],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'notes' => ['type' => 'textarea', 'rows' => 4],
                    'category' => ['type' => 'radio', 'options' => self::$categoryOptions, 'optionsDisplay' => 'translate',
                        'validationSometimes' => ['required', function ($input) {
                            // Category required if it isn't todo or ignored (not requiring for ignored because might be error, foreign, etc.)
                            return $input->contactStatus != 'todo' && $input->contactStatus != 'ignored';
                        }],
                    ],
                    'contactStatus' => ['searchType' => 'select', 'updateType' => 'radio', 'options' => self::$contactStatusOptions,
                        'optionsDisplay' => 'translate', 'determinesDynamicGroup' => 'contactStatusDynamic', ],
                    'contactStatusSpecific-ignored' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'ignored', 'modelPropertyName' => 'contactStatusSpecific',
                        'updateType' => 'radio', 'options' => self::$contactStatusSpecificOptions['ignored'], 'optionsDisplay' => 'translate',
                        'validation' => 'required_if:contactStatus,ignored',
                        'getValue' => function ($formHandler, $model) {
                            // Only return a value if this contactStatus is selected (to avoid validation errors)
                            return $formHandler->getFieldValue('contactStatus', true) == 'ignored' ? $model->contactStatusSpecific : '';
                        },
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($formHandler->getFieldValue('contactStatus', true) == 'ignored') {
                                $model->contactStatusSpecific = $value;
                            }
                        }, ],
                    'contactStatusSpecific-closed' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'closed', 'modelPropertyName' => 'contactStatusSpecific',
                        'updateType' => 'radio', 'options' => self::$contactStatusSpecificOptions['closed'], 'optionsDisplay' => 'translate', 'validation' => 'required_if:contactStatus,closed',
                        'getValue' => function ($formHandler, $model) {
                            // Only return a value if this contactStatus is selected (to avoid validation errors)
                            return $formHandler->getFieldValue('contactStatus', true) == 'closed' ? $model->contactStatusSpecific : '';
                        },
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($formHandler->getFieldValue('contactStatus', true) == 'closed') {
                                $model->contactStatusSpecific = $value;
                            }
                        }, ],
                    'name' => [
                        'maxLength' => 100,
                    ],
                    'contactEmails' => [
                        'dataTypeObject' => self::staticDataTypes()['contactEmails'], 'editType' => 'multi', 'comparisonType' => 'substring',
                        'maxLength' => 500, 'validation' => 'emailList',
                        'validationSometimes' => ['required_without:contactFormURL', function ($input) {
                            return $input->contactStatus == 'initialContact';
                        }],
                    ],
                    'invalidEmails' => ['dataTypeObject' => self::staticDataTypes()['invalidEmails'], 'editType' => 'display',
                        'comparisonType' => 'substring', 'maxLength' => 500, ],
                    'contactFormURL' => [
                        'validationSometimes' => ['required_without:contactEmails', function ($input) {
                            return $input->contactStatus == 'initialContact';
                        }],
                    ],
                    'placeSelector' => array_merge(self::placeSelectorFieldInfo(), [
                        'dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'todo,initialContact,discussing,closed,flagged',
                        'dynamicMethod' => 'hide', /* has to use 'hide' so selector2 will work */
                    ]),
                    'otherWebsitesLinked' => ['dataTypeObject' => self::staticDataTypes()['otherWebsitesLinked'], 'type' => 'checkboxes',
                        'dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'initialContact',
                        'options' => self::$otherWebsitesLinkedOptions, 'optionsDisplay' => 'translate', ],
                    'contactTopics' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'initialContact',
                        'dynamicMethod' => 'hide', // has to use 'hide' so the javascript can highlight topics based on the selected category
                        'dataTypeObject' => self::staticDataTypes()['contactTopics'], 'type' => 'checkboxes',
                        'options' => self::$contactTopicOptions, 'optionsDisplay' => 'translate', 'maxLength' => 500, ],
                    // Contact subject/message (not saved in the IncomingLink database)
                    'emailSubject' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'initialContact', 'dataAccessMethod' => 'none'],
                    'contactMessage' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'initialContact', 'type' => 'textarea', 'rows' => 15, 'dataAccessMethod' => 'none'],
                    'lastContact' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'discussing,closed,flagged', 'type' => 'display'],
                    'followUpStatus' => ['type' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ',
                        'dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'initialContact',
                        'checkboxText' => 'Send a follow-up email if no reply is received for ' . self::INITIAL_FOLLOW_UP_AFTER_DAYS . ' days.',
                        'getValue' => function ($formHandler, $model) {
                            return $model->followUpStatus == '' || $model->followUpStatus == 'todo';
                        },
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($value) {
                                $model->followUpStatus = 'todo';
                            }
                        },
                    ],
                    'reminderDate' => ['dynamicGroup' => 'contactStatusDynamic', 'dynamicGroupValues' => 'discussing,closed,flagged',
                        'dynamicMethod' => 'hide', // has to use 'hide' so date picker will work
                        'type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', ],
                ];

                if (auth()->user()->hasPermission('admin')) {
                    $fieldInfos['userID'] = [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ];
                }

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    public static function updateLastContactDatesByEmailAddress($emails): void
    {
        if (! $emails) {
            return;
        }

        $incomingLinks = self::byEmail($emails)->get();

        foreach ($incomingLinks as $incomingLink) {
            $incomingLink->updateLastContactDate();
        }
    }

    public static function setResponseReceivedByEmailAddress($emails): void
    {
        if (! $emails) {
            return;
        }

        $incomingLinks = self::byEmail($emails)->get();

        foreach ($incomingLinks as $incomingLink) {
            $incomingLink->responseReceived();
        }
    }

    public static function findMarketingUserToPayForBooking($fromURL)
    {
        $domain = WebsiteTools::getRootDomainName($fromURL);
        $possiblyMatchingLinks = self::where('contactStatus', 'closed')->whereIn('contactStatusSpecific', ['agreed', 'verified'])
            ->where('lastContact', '>=', Carbon::now()->subYears(1)->format('Y-m-d')) // we only pay them for links they've contacted in the past year
            ->where('domain', $domain)->get();

        foreach ($possiblyMatchingLinks as $possiblyMatchingLink) {
            if (stripos($fromURL, $possiblyMatchingLink->url) === 0 || stripos($possiblyMatchingLink->url, $fromURL) === 0) {
                if ($possiblyMatchingLink->user && $possiblyMatchingLink->user->isAllowedToLogin()
                    && $possiblyMatchingLink->user->hasPermission('staffMarketing')) {
                    return $possiblyMatchingLink->user;
                }
            }
        }

        return null; // no match found
    }

    /* Accessors & Mutators */

    public function setUrlAttribute($value): void
    {
        // Also upate the webStatus and websiteDomain automatically
        if (isset($this->attributes['url']) && $this->attributes['url'] != $value) {
            $this->attributes['url'] = $value;
            $this->pageAuthority = null; // so we know to re-check it later
            $previousDomain = $this->domain;
            $this->setDomain();
            if ($this->domain != $previousDomain) {
                $this->domainAuthority = null; // so we know to re-check it later
                $this->trafficRank = null; // so we know to re-check it later
            }
        }
    }

    public function getSpiderResultsAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setSpiderResultsAttribute($value): void
    {
        $this->attributes['spiderResults'] = ($value ? json_encode($value) : '');
    }

    /* Static */

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'hourly':
                set_time_limit(2 * 60 * 60); // Note: This also resets the timeout timer.
                $output .= 'Update Link Information: ';
                $incomingLinks = self::where('checkStatus', '')
                    ->where('contactStatus', '!=', 'ignored') // for now we don't bother with 'ignored' ones (which are mostly ones that already link to us)
                    ->orderBy('id', 'desc') // newest ones are usually more important than old ones
                    ->limit(25)->get();
                foreach ($incomingLinks as $incomingLink) {
                    $output .= "[$incomingLink->id] ";
                    $incomingLink->updateLinkInformation();
                    $incomingLink->save();
                }
                $output .= "\n";

                $output .= 'Update Traffic Ranks: ';
                // Don't know how much it's throttled to, so just trying to make it a reasonable limit
                // Note: For now we're only checking ones with 'ok' checkStatus. But we could also check other ones eventually.
                $links = self::whereNull('trafficRank')->where('checkStatus', 'ok')->orderBy('id', 'desc')->limit(100)->get();
                set_time_limit(300 + 1 * $links->count()); // we currently limit our fetching rate to 1 per second
                $output .= self::updateTrafficRanks($links) . "\n";

                break;

            case 'daily':
                $output .= 'Update Authority Stats: ';
                // Their free API allows up to 25k items per month.
                // Note: For now we're only checking ones with 'ok' checkStatus. But we could also check other ones eventually.
                $links = self::whereNull('pageAuthority')->where('checkStatus', 'ok')->orderBy('id', 'desc')->limit(700)->get();
                set_time_limit(300 + 21 * $links->count()); // Moz needs at least 10s per 10 links (it's throttled), but sometimes fails on the first try.
                $output .= self::updateAuthorityStats($links) . "\n";

                $output .= 'Assign IncomingLinks to users: ';
                $output .= self::assignTodoLinksToMarketingUsers() . "\n";

                break;

            case 'monthly':
                $output .= "Optimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function updateAuthorityStats($links)
    {
        $output = '';

        $urls = $links->pluck('url')->toArray();
        $results = WebsiteInfo::getAuthorityStats($urls);
        if ($results) {
            foreach ($results as $url => $result) {
                $link = $links->where('url', $url)->first();
                if (! $link) {
                    logWarning("No result found for '$url'.");

                    continue;
                }
                $link->pageAuthority = $result['pageAuthority'];
                $link->domainAuthority = $result['domainAuthority'];
                $link->setPriorityLevel(); // also update the priority since that will now have changed
                $link->save();

                $output .= "$link->id (pageAuthority:$link->pageAuthority, domainAuthority:$link->domainAuthority) ";
            }
        }

        return $output;
    }

    public static function updateTrafficRanks($links)
    {
        $output = '';

        foreach ($links as $link) {
            $trafficRank = WebsiteInfo::getTrafficStats($link->url);
            if ($trafficRank === null) {
                continue;
            } // errors already reported by getTrafficStats()
            $link->trafficRank = $trafficRank;
            $link->setPriorityLevel(); // also update the priority since that will now have changed
            $link->save();

            $output .= "$link->id (trafficRank:$trafficRank) ";
        }

        return $output;
    }

    public static function assignTodoLinksToMarketingUsers()
    {
        $output = '';

        $linkIDs = self::readyToDo()->orderBy('priorityLevel', 'DESC')->pluck('id');

        $marketingUsers = User::where('status', 'ok')->havePermission('staffMarketing')
            ->havePermission('admin', 'matchNone')->get() // (is not an admin user)
            ->sort(
                function ($userA, $userB) {
                    // staffMarketingLevel2 get sorted to the top so they get the higher priority links
                    return $userB->hasPermission('staffMarketingLevel2') - $userA->hasPermission('staffMarketingLevel2');
                }
            );

        if ($marketingUsers->count() == 0) {
            return $output;
        }

        $MAX_LINKS_PER_USER = 200;
        $linksPerUser = min($MAX_LINKS_PER_USER, ceil($linkIDs->count() / $marketingUsers->count()));
        $output .= "($linksPerUser links per user) ";

        if ($linksPerUser) {
            // Remove all existing assignments
            self::where('contactStatus', 'todo')->update(['userID' => 0]);

            foreach ($marketingUsers as $user) {
                if ($user->hasPermission('staffMarketingLevel2')) {
                    // get the highest priority ones
                    $linkChunk = $linkIDs->splice(0, $linksPerUser);
                } else {
                    // get the lowest priority ones
                    $linkChunk = $linkIDs->splice($linkIDs->count() > $linksPerUser ?
                        $linkIDs->count() - $linksPerUser : 0);
                }

                $output .= "[user $user->id: assigning " . $linkChunk->count() . '] ';
                self::whereIn('id', $linkChunk)->update(['userID' => $user->id]);
            }
        } else {
            logWarning('0 todo incoming links per user (no more needing to be done?).');
        }

        return $output;
    }

    // Returns 'exists', 'created', or an error message.  Sets $linkObject to the new or existing link.

    public static function addNewLink($attributes, &$linkObject = null, $ignoreExistingDomain = false)
    {
        $new = new self($attributes);
        $validationError = $new->validateAndFillInMissingValues();
        if ($validationError != '') {
            return $validationError;
        }

        $existingLinks = $new->otherLinksOfSameDomain();
        foreach ($existingLinks as $existingLink) {
            if (($ignoreExistingDomain || $new->allowingMultipleURLsToThisDomain() || $existingLink->isErrorPage())
                && $new->url != $existingLink->url) {
                continue;
            } // same domain but different url allowed in certain cases

            if ($new->linksToUs() && ! $existingLink->linksToUs()) {
                // If they didn't link to us before, but now do, in some cases we delete the existing link...
                switch ($existingLink->contactStatus) {
                    case 'todo':
                    case 'ignored':
                        // Now that we know they already link to us, delete the 'todo' link.
                        $existingLink->delete();

                        continue 2;

                    default: // 'discussing', 'closed', etc.
                        continue 2; // ignore the existing link (will go ahead and add the new link to us to the database if doesn't otherwise exist)
                }
            }

            $linkObject = $existingLink;

            return 'exists'; // link already exists
        }

        $new->save();
        $linkObject = $new;

        return 'created';
    }

    /* Misc */

    public function otherAffiliateSitesLinked()
    {
        $otherAffiliateSitesLinked = [];

        if ($this->otherWebsitesLinked) {
            foreach ($this->otherWebsitesLinked as $site) {
                if (strpos($site, 'Affiliate') !== false) {
                    $otherAffiliateSitesLinked[] = str_replace(' Affiliate', '', $site);
                }
            }
        }

        return $otherAffiliateSitesLinked;
    }

    public function isErrorPage()
    {
        return $this->checkStatus == 'error' || ($this->contactStatus == 'ignored' && $this->contactStatusSpecific == 'error');
    }

    public function allContactAddresses($includingContactFormURL = true)
    {
        $result = $this->contactEmails;
        if ($includingContactFormURL && $this->contactFormURL != '') {
            $result[] = $this->contactFormURL;
        }
        if ($this->invalidEmails) {
            $result = array_merge($result, $this->invalidEmails);
        }

        return $result;
    }

    // Returns error string or '' if everything is ok.

    public function validateAndFillInMissingValues()
    {
        if ($this->url == '' || ! filter_var($this->url, FILTER_VALIDATE_URL)) {
            return "Invalid URL '$this->url'.";
        }
        if ($this->contactStatus == '') {
            return 'Missing contactStatus.';
        }
        if ($this->source == '') {
            return 'Missing source.';
        }
        if (! $this->createDate) {
            $this->createDate = date('Y-m-d');
        }

        return '';
    }

    public function setDomain(): void
    {
        if ($this->url == '') {
            throw new Exception('Missing URL.');
        }
        $domain = WebsiteTools::getRootDomainName($this->url);

        if ($this->domain != '') {
            if ($this->domain != $domain) {
                throw new Exception("Domain '$this->domain' didn't match '$domain' for $this-id.");
            }
        } else {
            $this->domain = $domain;
        }
        if ($this->domain == '') {
            throw new Exception("Couldn't set domain for '$this->url'.");
        }
    }

    public function setPriorityLevel()
    {
        $domainAuthority = ($this->domainAuthority > 0 ? $this->domainAuthority : 20); // if domainAuthority is unknown, use an average one
        $pageAuthority = ($this->pageAuthority > 0 ? $this->pageAuthority : 20); // if pageAuthority is unknown, use an average one
        $this->priorityLevel = $pageAuthority + $domainAuthority;
        if ($this->trafficRank && $this->trafficRank < 1500) {
            $this->priorityLevel = $this->priorityLevel + 15;
        }
        if ($this->trafficRank && $this->trafficRank < 600) {
            $this->priorityLevel = $this->priorityLevel + 15;
        }
        if (Str::endsWith($this->domain, '.gov')) {
            $this->priorityLevel = $this->priorityLevel + 80;
        }
        if (Str::endsWith($this->domain, '.edu')) {
            $this->priorityLevel = $this->priorityLevel + 70;
        }
        if (Str::endsWith($this->domain, '.org')) {
            $this->priorityLevel = $this->priorityLevel + 50;
        }
        if (carbonFromDateString($this->createDate)->lt(Carbon::now()->subYears(3))) { // Older than 3 years
            $this->priorityLevel = $this->priorityLevel - 30;
        } elseif (carbonFromDateString($this->createDate)->gt(Carbon::now()->subYears(1))) { // Newer than 1 year
            $this->priorityLevel = $this->priorityLevel + 30;
        }
        if ($this->category == 'tour') {
            $this->priorityLevel = $this->priorityLevel + 100;
        } // when we get 'tour' category links (such as scraping DMOZ)

        return $this; // for function chaining convenience
    }

    public function otherLinksOfSameDomain()
    {
        if ($this->domain == '') {
            throw new Exception('Domain not set.');
        }

        return self::where('domain', $this->domain)
            ->where('id', '!=', intval($this->id)) // (could be 0 if this one isn't yet saved, but that's ok)
            ->get();
    }

    public function allowingMultipleURLsToThisDomain()
    {
        if ($this->domain == '') {
            throw new Exception('Domain not set.');
        }

        return in_array($this->domain, self::$allowMultipleURLsForDomains);
    }

    public function linksToUs()
    {
        return ($this->contactStatus == 'ignored' && $this->contactStatusSpecific == 'already') ||
            (! $this->allowingMultipleURLsToThisDomain() && $this->spiderResults && isset($this->spiderResults['Hostelz']));
    }

    public function updateLastContactDate($andSave = true): void
    {
        $this->lastContact = date('Y-m-d');
        if ($andSave) {
            $this->save();
        }
    }

    public function responseReceived($andSave = true): void
    {
        if ($this->contactStatus == 'initialContact') {
            $this->contactStatus = 'discussing';
        }
        $this->updateLastContactDate($andSave);
    }

    // Note that this doesn't save() the record, it just sets the values.

    public function updateLinkInformation($forceUpdateAll = false)
    {
        $this->lastCheck = date('Y-m-d');

        // CheckStatus
        $checkResult = WebsiteStatusChecker::getWebsiteStatus($this->url, false, true, $contents);
        $this->checkStatus = ($checkResult < WebsiteStatusChecker::$websiteStatusOptions['unknown'] ? 'error' : 'ok');
        if ($this->checkStatus == 'error') {
            return $this;
        }

        // Language
        if ($forceUpdateAll || $this->language == '') {
            $stripped = getPlainTextFromHTMLPage($contents);
            if (strlen($stripped) > 200) {
                $this->language = LanguageDetection::detect($stripped);
            }
        }

        // Title
        if ($forceUpdateAll || $this->pageTitle == '') {
            if (preg_match('!<title>(.*?)</title>!i', $contents, $matches)) {
                $this->pageTitle = trim(html_entity_decode($matches[1]));
            }
        }

        // Pagerank
        /* (our pagerank fetcher no longer works)
        if ($forceUpdateAll || (!$this->pagerank && $this->contactStatus == 'todo')) { // (Currently not getting pagerank for our existing links -- too much unnecessary mining of Google's servers.)
        	$newPagerank = file_get_contents("http://hostelinfo.com/prFetch.php?url=".urlencode($this->url));
        	if($newPagerank > 0) $this->pagerank = trim($newPagerank); // only use the new page rank if we got a > 0 value, otherwise we keep the old value.
        }
        */

        // spiderResults
        $this->spider($forceUpdateAll);

        // otherWebsitesLinked
        if ($this->spiderResults) {
            // Set otherWebsitesLinked
            $otherWebsitesLinked = $this->otherWebsitesLinked;
            foreach (self::$otherWebsitesLinkedOptions as $site) {
                if (isset($this->spiderResults[$site]) && ! in_array($site, $otherWebsitesLinked)) {
                    $otherWebsitesLinked[] = $site;
                }
            }
            $this->otherWebsitesLinked = $otherWebsitesLinked;

            // Automatically set to ignored if they already link to us.
            if (isset($this->spiderResults['Hostelz']) && $this->contactStatus == 'todo') {
                $this->contactStatus = 'ignored';
                $this->contactStatusSpecific = 'already';
            }
        }

        // Priority
        $this->setPriorityLevel();

        // Find if matches a listing
        if ($this->placeType == '') {
            $listing = Listing::where('websiteDomain', $this->domain)->areLiveOrNew()->first();
            if ($listing) {
                $this->placeType = 'Listing';
                $this->placeID = $listing->id;
                if ($this->category == '') {
                    $this->category = 'accommodation';
                }
            }
        }

        return $this; // for chaining
    }

    public function spider($forceUpdate = false): void
    {
        $spider = new Spider();
        $spider->maxTotalPages = 10;

        // Note: These keys should match the names in $otherWebsitesLinkedOptions
        // so we can use the spider results to set $this->otherWebsitesLinked.

        $linkPatterns = [
            'Hostelz' => '`https?\:\/\/(^|.+\.)hostelz\.com(.+?)$`i',
            'Hostelworld' => '`https?\:\/\/(^|.+\.)hostelworld\.com(.+?)$`i',
            'Hostelworld Affiliate' => '`https?\:\/\/(^|.+\.)(bookhostels\.com|hostelworld\.com(.+?)\?affiliate=)(.+?)$`i',
            'HostelBookers' => '`https?\:\/\/(^|.+\.)hostelbookers\.com(.+?)$`i',
            'HostelBookers Affiliate' => '`https?\:\/\/(^|.+\.)(hb-247\.com|hostelbookers\.com(.+?)\?affiliate=)(.+?)$`i',
            'Hostels.com' => '`https?\:\/\/(^|.+\.)hostels\.com(.+?)$`i',
            'HostelsClub' => '`https?\:\/\/(^|.+\.)hostelsclub\.com(.+?)$`i',
            'Gomio' => '`https?\:\/\/(^|.+\.)gomio\.com(.+?)$`i',
            // Possible contacts:
            'mailto' => '`mailto\:(.+?)$`i',
            'Facebook' => '`https?\:\/\/(^|.+\.)facebook\.com(.+?)$`i',
            'Twitter' => '`https?\:\/\/(^|.+\.)twitter\.com(.+?)$`i',
            'LinkedIn' => '`https?\:\/\/(^|.+\.)linkedin\.com(.+?)$`i',
        ];

        // (Note that if it was spidered recently this will just return cached results.)
        $this->spiderResults = $spider->spiderSiteWithCaching($this->url, 2, $linkPatterns, 'domain', 'incomingLink', true, 365, $forceUpdate);
    }

    public function competitorLinkSpiderResults()
    {
        $result = [];
        if ($this->spiderResults) {
            foreach ($this->spiderResults as $linkType => $links) {
                if (! in_array($linkType, ['mailto', 'Facebook', 'Twitter', 'LinkedIn'])) {
                    $result[$linkType] = $links;
                }
            }
        }

        return $result;
    }

    public function contactLinkSpiderResults()
    {
        $result = [];
        if ($this->spiderResults) {
            foreach ($this->spiderResults as $linkType => $links) {
                if (in_array($linkType, ['mailto' /* (not using currently for contacting websites:) , 'Facebook', 'LinkedIn' */])) {
                    $result[$linkType] = $links;
                }
            }
        }

        return $result;
    }

    /* Scopes */

    // Search contactEmails and invalidEmails for matching email, or an array of email addresses.

    public function scopeByEmail($query, $emails)
    {
        return $query->where(function ($query) use ($emails): void {
            $query->where(function ($query) use ($emails): void {
                self::staticDataTypes()['contactEmails']->searchQuery($query, (array) $emails, 'matchAny');
            })->orWhere(function ($query) use ($emails): void {
                self::staticDataTypes()['invalidEmails']->searchQuery($query, (array) $emails, 'matchAny');
            });
        });
    }

    public function scopeReadyTodo($query)
    {
        return $query->where('contactStatus', 'todo')->where('checkStatus', 'ok')
            ->whereIn('language', ['en', '']) // (only doing english for now) (todo other languages later?)
            ->where('trafficRank', '<', 2000000) // probably a good idea?
            ->where('domainAuthority', '>=', 20); // probably a reasonable mininum to bother with?
    }

    public function scopeInitialFollowUpDue($query)
    {
        return $query->where('contactStatus', 'initialContact')->where('followUpStatus', 'todo')->where('contactEmails', '!=', '')
            ->where('lastContact', '<=', Carbon::now()->subDays(self::INITIAL_FOLLOW_UP_AFTER_DAYS)->format('Y-m-d'));
    }

    /* Relationships */

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }
}
