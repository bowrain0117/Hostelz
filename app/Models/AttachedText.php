<?php

namespace App\Models;

use App\Enums\Listing\CategoryPage;
use App\Events\AttachedTextUpdated;
use App\Lib\AttachedText\Shotrcodes\HostelName;
use App\Lib\AttachedText\Shotrcodes\HostelNameLink;
use App\Lib\AttachedText\Shotrcodes\HostelNameOrder;
use App\Lib\AttachedText\Shotrcodes\OtaMainLink;
use App\Models\Listing\Listing;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Pipeline\Pipeline;
use Lib\BaseModel;
use Lib\Emailer;
use Lib\PlagiarismChecker;

class AttachedText extends BaseModel
{
    use HasFactory;

    protected $table = 'attached';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $statusOptions = ['draft', 'submitted', 'ok', 'denied', 'returned', 'flagged'];

    public static $subjectTypeOptions = ['cityInfo', 'countryInfo', 'hostels', 'imported', 'continentInfo'];

    public const DAYS_TO_HOLD_DRAFT_DESCRIPTIONS = 21;

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':

                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'editType' => 'display'],
                    // not an actual database field
                    'subject_name' => ['type' => 'display', 'editType' => 'display', 'listType' => 'display', 'getValue' => function ($formHandler, $model) {
                        return $model->nameOfSubject();
                    }],
                    'subjectType' => ['type' => 'select', 'options' => self::$subjectTypeOptions, 'maxLength' => 100],
                    'subjectID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'subjectString' => ['maxLength' => 200],
                    'type' => ['maxLength' => 100, 'comparisonType' => 'equals'],
                    'notes' => ['type' => 'textarea', 'rows' => 2],
                    'source' => ['maxLength' => 100, 'comparisonType' => 'equals'],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate' /* [ 'allowWritingToDB' => true,] */],
                    'score' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax', 'sanitize' => 'int'],
                    'lastUpdate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 80],
                    'plagiarismCheckDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 80],
                    'plagiarismPercent' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax', 'sanitize' => 'int'],
                    'plagiarismInfo' => ['type' => 'textarea', 'rows' => 4],
                    'dataBeforeEditing' => ['type' => 'textarea', 'rows' => 4],
                    'data' => ['type' => 'WYSIWYG', 'rows' => 20],
                    'comments' => ['type' => 'display', 'searchType' => 'text', 'unescaped' => true],
                    'newComment' => ['type' => 'ignore', 'editType' => 'textarea', 'rows' => 2,
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($value != '') {
                                $model->addComment($value, 'staff');
                            }
                        },
                    ],
                ];

                if ($purpose == 'staffEdit') {
                    $useFields = ['id', 'subject_name', 'status', 'language', 'data', 'notes'];
                    $displayOnly = ['type', 'subjectType', 'subjectID', 'subjectString'];
                    $fieldInfos['id']['type'] = 'ignore';
                    array_walk($fieldInfos, function (&$fieldInfo, $fieldName, $displayOnly): void {
                        if (in_array($fieldName, $displayOnly)) {
                            $fieldInfo['editType'] = 'display';
                        }
                    }, $displayOnly);
                    $fieldInfos = array_intersect_key($fieldInfos, array_flip(array_merge($useFields, $displayOnly)));
                    // We need to be able to search by the score.
                    $fieldInfos['score'] = ['comparisonType' => 'equals', 'type' => 'ignore'];

                    return $fieldInfos;
                } else {
                    return $fieldInfos;
                }

                break;

            case 'placeDescriptions':
                return [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'language' => ['type' => 'select', 'options' => Languages::allLiveSiteCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'data' => ['type' => 'textarea', 'rows' => 20],
                    'comments' => ['type' => 'display', 'searchType' => 'text', 'unescaped' => true],
                    'newComment' => ['type' => 'ignore', 'editType' => 'textarea', 'rows' => 2,
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($value != '') {
                                $model->addComment($value, 'user');
                            }
                        },
                    ],
                ];

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }
    }

    protected static function booted(): void
    {
        static::updated(function (self $item) {
            AttachedTextUpdated::dispatch($item);
        });
    }

    /* Accessors & Mutators */

    protected function text(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->replaceShortcodes(),
        );
    }

    // * Static Methods *

    // $data is an array of languageCode => data elements.
    // pass null for $subjectString if not using it.

    /*
        Call convertIncorrectCharset($data); if needed first (if there are issues with the character set of the text)
        Note that the $setStatus status is only used if the data is new or was modified.
    */

    public static function replaceAllLanguages($subjectType, $subjectID, $subjectString, $type, $source, $data, $replaceOnlyThisSource = true, $insertDataIfEmpty = false, $setStatus = 'ok'): void
    {
        if (($subjectID == 0 && $subjectString == '') || $subjectType == '' || $type == '') {
            throw new Exception('Missing parameter.');
        }

        $query = self::where('subjectType', $subjectType)->where('subjectID', $subjectID)->where('type', $type);
        if ($subjectString !== null) {
            $query->where('subjectString', $subjectString);
        }
        if ($replaceOnlyThisSource) {
            $query->where('source', $source);
        }
        $existingRecords = $query->get();

        foreach ($data as $language => $datum) {
            if (is_array($datum)) {
                $datum = self::serializeData($datum);
            }
            if ($datum == '' && ! $insertDataIfEmpty) {
                continue;
            } // the data was empty, so skip it

            $record = null;

            if (! $existingRecords->isEmpty()) {
                // Re-use any existing records that are the same (and keep the score/plagiarism info only if the data didn't change).
                foreach ($existingRecords as $key => $existing) {
                    if ($existing->language == $language) {
                        $record = $existing;
                        if ($record->data != $datum) {
                            // Make sure the score and plagiarism info is reset if the data changed
                            $record->score = 0;
                            $record->plagiarismPercent = 0;
                            $record->plagiarismInfo = '';
                            $record->plagiarismCheckDate = null;
                            $record->data = $datum;
                            $record->lastUpdate = date('Y-m-d');
                            //echo "(replace $record->id) ";
                        }
                        $existingRecords->forget($key); // so we know not to delete this one

                        break;
                    }
                }
            }

            if (! $record) {
                $record = new self([
                    'subjectType' => $subjectType,
                    'subjectID' => $subjectID,
                    'type' => $type,
                    'language' => $language,
                    'data' => $datum,
                    'status' => ($setStatus === false ? '' : $setStatus),
                    'lastUpdate' => date('Y-m-d'),
                ]);
            }

            // Set fields that need to be set whether it's new or updating an existing record:
            $record->subjectString = (string) $subjectString;
            $record->source = $source;
            if ($setStatus !== false) {
                $record->status = $setStatus;
            }

            $record->save();
        }

        // Delete any left over records that are no longer used
        if (! $existingRecords->isEmpty()) {
            // Re-use any existing records that are the same
            foreach ($existingRecords as $existing) {
                //echo "(delete ".$existing->id.") ";
                $existing->delete();
            }
        }
    }

    public static function serializeData($data)
    {
        return base64_encode(serialize($data)); // have to base64_encode because otherwise problems storing serialized data in MySQL.
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'daily':

                set_time_limit(3 * 60 * 60); // Note: This also resets the timeout timer.

                $output .= 'Delete expired descriptions ';

                $attachedTexts = self::whereIn('subjectType', ['countryInfo', 'cityInfo'])->where('type', 'description')->whereIn('status', ['returned', 'draft'])
                    ->where('lastUpdate', '<', Carbon::now()->subDays(self::DAYS_TO_HOLD_DRAFT_DESCRIPTIONS))->get();

                foreach ($attachedTexts as $attachedText) {
                    // Give them more time if they actually already entered something
                    if ($attachedText->data != '' &&
                        Carbon::now()->lt($attachedText->expirationDate()->addDays(self::DAYS_TO_HOLD_DRAFT_DESCRIPTIONS + 90))) {
                        continue;
                    }
                    $output .= $attachedText->id . ' ';
                    $attachedText->delete();
                }

                $output .= "\n";

                $output .= 'Check submitted descriptions for issues: ';

                // Useful online text comparison tools: http://www.compareitnow.net/  http://www.comparesuite.com

                // Note: non-new rows are for comparison purposes only (below)
                $rows = self::whereIn('subjectType', ['countryInfo', 'cityInfo'])->where('type', 'description')->where('data', '!=', '')->get();

                foreach ($rows as $row) {
                    if ($row->status != 'submitted' || $row->score != 0) {
                        continue;
                    } // non-new rows are for comparison purposes only (below)

                    $output .= $row->id . ' ';

                    // * Check the Length *

                    // We add some more words to the count because this count tends
                    // to be a little different than the one they see in the editor window.
                    $wordCount = str_word_count($row->data) + 15;

                    if ($row->subjectType == 'cityInfo') {
                        if ($wordCount < CityInfo::CITY_DESCRIPTION_MINIMUM_WORDS) {
                            $row->addComment('This city description is too short (' . CityInfo::CITY_DESCRIPTION_MINIMUM_WORDS . ' words minimum). [This is an automatically-generated response from our text analyzer script.]');
                            $row->status = 'returned';
                            $row->save();
                            $output .= '(too short) ';

                            continue;
                        }
                    } elseif ($row->subjectType == 'countryInfo' && $row->subjectString == '') {
                        if ($wordCount < CountryInfo::COUNTRY_DESCRIPTION_MINIMUM_WORDS) {
                            $row->addComment('This country description is too short (' . CountryInfo::COUNTRY_DESCRIPTION_MINIMUM_WORDS . ' words minimum).  [This is an automatically-generated response from our text analyzer script.]');
                            $row->status = 'returned';
                            $row->save();
                            $output .= '(too short) ';

                            continue;
                        }
                    } elseif ($row->subjectType == 'countryInfo' && $row->subjectString != '') {
                        if ($wordCount < CountryInfo::REGION_DESCRIPTION_MINIMUM_WORDS) {
                            $row->addComment('This region description is too short (' . CountryInfo::REGION_DESCRIPTION_MINIMUM_WORDS . ' words minimum).  [This is an automatically-generated response from our text analyzer script.]');
                            $row->status = 'returned';
                            $row->save();
                            $output .= '(too short) ';

                            continue;
                        }
                    } else {
                        throw new Exception("This shouldn't happen, all the types were checked.");
                    }

                    // * Check for use of word "hostel" *

                    if ($row->language == 'en') {
                        $hostelCount = substr_count(strtolower($row->data), 'hostel');
                        if ($hostelCount < 3) {
                            $row->addComment('This type of description must mention the word "hostel" or "hostels" at least 3 times. [This is an automatically-generated response from our text analyzer script.]');
                            $row->status = 'returned';
                            $row->save();
                            $output .= '(not enough "hostel" mentions) ';

                            continue;
                        }
                    }

                    // * Check for Similar Descriptions *

                    foreach ($rows as $comparisonKey => $comparisonRow) {
                        if ($row->id == $comparisonRow->id) {
                            continue;
                        }

                        $similar = similar_text($row->data, $comparisonRow->data, $percent);
                        $percent = round($percent);
                        if ($percent > 40) {
                            $subjectName = $comparisonRow->nameOfSubject();
                            $row->addComment("This description uses too many words and phrases that are similar to the description for $subjectName. [This is an automatically-generated response from our text analyzer script.]");
                            $row->status = 'returned';
                            $row->save();
                            $output .= "returned (similar $percent% to $subjectName). ";
                            logError("Description $row->id returned (similar $percent% to $subjectName).", [], 'alert');

                            continue 2;
                        }
                    }

                    $row->score = 100; // signals that we've analyzed it, so it can be edited and approved.  TO DO: Is this being used this way?  Should it have a different status instead?
                    $output .= '(ok) ';

                    // * Check for Plagiarism *

                    if (! $row->plagiarismCheckDate) {
                        $result = PlagiarismChecker::textCheck($row->data);
                        if ($result == false) {
                            logError("PlagiarismChecker returned an error for $row->id.");

                            continue;
                        } else {
                            $output .= '(plagiarism percentMatched: ' . $result['percentMatched'] . ') ';
                            $row->plagiarismCheckDate = date('Y-m-d');
                            $row->plagiarismPercent = $result['percentMatched'];
                            $row->plagiarismInfo = $result['details'];

                            if ($row->plagiarismPercent >= 13) {
                                logError("Plagiarism score of $row->plagiarismPercent for attached text $row->id.", [], 'alert');
                            }
                        }
                    }

                    // * Save *

                    $row->save();
                }
                $output .= "\n";

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    // * Misc Methods *

    public function expirationDate()
    {
        return carbonFromDateString($this->lastUpdate)->addDays(self::DAYS_TO_HOLD_DRAFT_DESCRIPTIONS);
    }

    public function nameOfSubject()
    {
        switch ($this->subjectType) {
            case 'cityInfo':
                $cityInfo = CityInfo::find($this->subjectID);
                if (! $cityInfo) {
                    return null;
                }

                return $cityInfo->fullDisplayName();

            case 'countryInfo':
                $countryInfo = CountryInfo::find($this->subjectID);
                if (! $countryInfo) {
                    return null;
                }
                if ($this->subjectString === '') {
                    return $countryInfo->translation()->country;
                } else {
                    // (might also be a cityGroup descriptions, but the URL is the same either way)
                    return $countryInfo->regionFullDisplayName($this->subjectString, true);
                }

                // no break
            case 'imported':
                $imported = Imported::find($this->subjectID);
                if (! $imported) {
                    return null;
                }

                return $imported->name;

            case 'hostels':
                $listing = Listing::find($this->subjectID);
                if (! $listing) {
                    return null;
                }

                return $listing->name;

            case 'continentInfo':
                return $this->subjectString;

            default:
                return null;
        }
    }

    public function urlOfSubject($urlType = 'auto', $language = null)
    {
        switch ($this->subjectType) {
            case 'cityInfo':
                $cityInfo = CityInfo::find($this->subjectID);
                if (! $cityInfo) {
                    return null;
                }

                if ($this->type === 'description') {
                    return $cityInfo->getURL($urlType, $language);
                }

                if ($category = CategoryPage::tryFromTableKey($this->type)) {
                    return $category->url($cityInfo);
                }

                return $cityInfo->getSpecialHostelsURL($this->type, $urlType, $language); // cheapHostels, etc.

                // no break
            case 'countryInfo':
                $countryInfo = CountryInfo::find($this->subjectID);
                if (! $countryInfo) {
                    return null;
                }

                if ($this->subjectString === '') {
                    return $countryInfo->getURL($urlType, $language);
                }

                return $countryInfo->getRegionURL($this->subjectString, $urlType, $language, true);

                // no break
            case 'imported':
                $imported = Imported::find($this->subjectID);
                if (! $imported) {
                    return null;
                }

                return routeURL('staff-importeds', $this->subjectID);

            case 'hostels':
                return Listing::find($this->subjectID)?->getURL($urlType, $language);

            case 'continentInfo':
                return routeURL('allContinents');

            default:
                return null;
        }
    }

    public function setDataSerialized($data): void
    {
        $this->data = self::serializeData($data);
    }

    public function getUnserializedData()
    {
        return unserialize(base64_decode($this->data));
    }

    public function addComment($comment, $asStaffOrUser = 'staff')
    {
        if ($comment == '') {
            throw new Exception('Empty comment.');
        }

        if ($asStaffOrUser == 'staff') { // If we're writing a comment, notify the user...
            if (! $this->user) {
                logError('No user with id ' . $this->userID . '.');

                return false;
            }
            if (($this->subjectType == 'cityInfo' || $this->subjectType == 'countryInfo') && $this->type == 'description') {
                $emailMessage = "A Hostelz.com staff person has added a new comment to one of your Place Descriptions.\n\n" .
                    "To view the comment, see your descriptions list:\n\n " . routeURL('placeDescriptions', [], 'publicSite');
            } else {
                throw new Exception('Unknown attached subjectType/type for attached ' . $this->id . '.');
            }
            Emailer::send(
                $this->user,
                'New Hostelz.com Staff Comment',
                'generic-email',
                ['text' => $emailMessage],
                config('custom.adminEmail')
            );
        }

        $this->comments = $this->comments . '(' . date('M j, Y') . ') <b>' . ($asStaffOrUser == 'staff' ? 'Hostelz.com:' : 'User:') . '</b> ' . htmlspecialchars($comment) . "<br>\n";

        return true;
    }

    /*  scope   */

    public function scopeContinentInfo(Builder $query, string $continent): Builder
    {
        return $query
            ->where('subjectType', 'continentInfo')
            ->where('subjectString', $continent)
            ->where('type', 'description')
            ->where('language', Languages::currentCode());
    }

    public function scopeHostelsByType($query, $type)
    {
        $query->where([
            ['subjectType', 'hostels'],
            ['type', $type],
            ['language', Languages::currentCode()],
        ]);
    }

    /* Relationships */

    // This only works if the source is a userID (which it is in most cases)

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    private function replaceShortcodes(): ?string
    {
        return app(Pipeline::class)
            ->send($this)
            ->through([
                HostelName::class,
                HostelNameOrder::class,
                HostelNameLink::class,
                OtaMainLink::class,
            ])
            ->then(fn ($item) => $item->data);
    }
}
