<?php

namespace App\Models;

use App\Models\Listing\Listing;
use App\Services\Imported\DownloadPicsImported;
use App\Services\ImportSystems\ImportSystems;
use App\Services\ImportSystems\ImportSystemServiceInterface;
use App\Utils\FieldInfo;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Lib\BaseModel;
use Lib\Currencies;

/**
 * @property ImportSystems $importSystem
 * @property ImportSystemServiceInterface $importService
 */
class Imported extends BaseModel
{
    use Prunable;
    use HasFactory;

    protected $table = 'imported';

    public static $staticTable = 'imported'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public static $statusOptions = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    public static $ratingCriteria = ['cleanliness', 'staff', 'location', 'atmosphere', 'security', 'overall']; // (in the order they're displayed on listing pages)

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const MAX_PICS_PER_IMPORTED = 12;

    public const INACTIVE_PICS_COUNT = 3;

    public const PIC_MIN_WIDTH = 150;

    public const PIC_MIN_HEIGHT = 150;

    public function delete(): void
    {
        foreach ($this->picsObjects as $pic) {
            $pic->delete();
        }
        foreach ($this->attachedTexts as $item) {
            $item->delete();
        }
        parent::delete();
    }

    /* Static */

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'pics' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'pics']),
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
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'statusListings' => [
                        'type' => 'select', 'options' => Listing::$statusOptions, 'optionsDisplay' => 'translateKeys',
                        'searchQuery' => function ($formHandler, $query, $value) {
                            if ($value !== '') {
                                return $query
                                    ->leftJoin('listings', 'imported.hostelID', '=', 'listings.id')
                                    ->where('listings.verified', $value)
                                    ->orderBy('imported.name');
                            }

                            return $query;
                        },
                        'setValue' => function ($formHandler, $model, $value): void {
                        },
                        'getValue' => function ($formHandler, $model) {
                            return $model->listing->verified ?? '';
                        },
                    ],
                    'system' => ['type' => 'select', 'options' => ImportSystems::allNamesKeyedByDisplayName(), 'optionsDisplay' => 'keys'],
                    'listing_created' => [
                        'type' => 'select', 'showBlankOption' => false, 'options' => ['all', 'no', 'yes'], 'optionsDisplay' => 'translate',
                        'searchQuery' => function ($formHandler, $query, $value) {
                            if ($value === 'no') {
                                return $query->where('hostelID', '0');
                            }
                            if ($value === 'yes') {
                                return $query->where('hostelID', '>', '0');
                            }

                            return $query;
                        },
                        'setValue' => function ($formHandler, $model, $value): void {
                        },
                        'getValue' => function ($formHandler, $model) {
                            return $model->hostelID ? 1 : 0;
                        },
                    ],
                    'propertyType' => ['type' => 'select', 'options' => Listing::propertyTypes(), 'validation' => 'required'],
                    'hostelID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('hostelID') === 'display')
                            && $model->listing ? $model->listing->fullDisplayName() : $model->hostelID;
                        }, ],
                    'name' => ['maxLength' => 100],
                    'previousName' => ['type' => 'display'],
                    'address1' => ['maxLength' => 255],
                    'address2' => ['maxLength' => 255],
                    'city' => ['maxLength' => 150],
                    'region' => ['maxLength' => 70],
                    'zipcode' => ['maxLength' => 150],
                    'country' => ['maxLength' => 150],
                    'theirCityCode' => [],
                    'latitude' => ['maxLength' => 20],
                    'longitude' => ['maxLength' => 20],
                    'email' => ['maxLength' => 255],
                    'web' => ['maxLength' => 255],
                    'tel' => ['maxLength' => 255],
                    'fax' => ['maxLength' => 255],
                    'other' => ['type' => 'textarea', 'rows' => 3],
                    'pics' => ['dataTypeObject' => self::staticDataTypes()['pics'], 'editType' => 'multi', 'comparisonType' => 'substring'],
                    'maxPeople' => [],
                    'maxNights' => [],
                    'arrivalEarliest' => [],
                    'arrivalLatest' => [],
                    'localCurrency' => [],
                    'privatePrice' => ['editType' => 'display'],
                    'sharedPrice' => ['editType' => 'display'],
                    /*
                    TO DO: I think these fields are no longer used?
                    'bedPriceUSD' => [ 'editType' => 'display' ],
                    'bedPriceEUR' => [ 'editType' => 'display' ],
                    'bedPriceGBP' => [ 'editType' => 'display' ],
                    */
                    'intCode' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'charCode' => ['maxLength' => 255],
                    'urlLink' => ['maxLength' => 255],
                    'availability' => ['type' => 'select', 'options' => ['1', '0'], 'optionsDisplay' => 'translate'],
                    'checkedAvail' => ['type' => 'select', 'editType' => 'display', 'options' => ['0', '-1', '1'], 'optionsDisplay' => 'translate'],
                    'specialCode' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'version' => ['editType' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'features' => ['type' => 'text', 'editType' => 'display', 'getValue' => function ($formHandler, $model) {
                        return $model->attributes['features'] ?? null; // just output the json encoded string
                    }],
                    'rating' => ['type' => 'text', 'editType' => 'display', 'getValue' => function ($formHandler, $model) {
                        return json_encode($model->rating); // (original was serialized) just output the json encoded string
                    }],
                    'updated_at' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType'],
                    'created_at' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType'],
                ];

                if ($purpose === 'staffEdit') {
                    $staffEditable = ['hostelID'];
                    $staffIgnore = ['previousName', 'version', 'features', 'intCode', 'charCode', 'specialCode'];
                    FieldInfo::fieldInfoType($fieldInfos, $staffEditable, $staffIgnore);
                }

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    /* Misc */

    public function hasLatitudeAndLongitude()
    {
        return (float) $this->latitude !== 0.0 || (float) $this->longitude !== 0.0;
    }

    public function getImportSystem()
    {
        return $this->importSystem;
    }

    public function determineLocalCurrency()
    {
        if ($this->localCurrency !== '' && Currencies::isKnownCurrencyCode($this->localCurrency)) {
            return $this->localCurrency;
        }
        if ($this->listing) {
            return $this->listing->determineLocalCurrency();
        }
    }

    public function createListing($andSave = true): Listing
    {
        $listing = new Listing([
            'name' => $this->name,
            'address' => $this->address1 . ($this->address2 !== '' ? ', ' . $this->address2 : ''),
            'city' => $this->city,
            'country' => $this->country,
            'zipcode' => $this->zipcode,
            /* 'web=' ... (we let updateListing() do this instead since it can validate it and pick the best url to use) */
            'tel' => $this->tel,
            'fax' => $this->fax,
            'propertyType' => $this->propertyType,
            'verified' => Listing::$statusOptions[$this->getImportSystem()->newListingStatus],
            'source' => '[' . $this->system . ':' . ($this->intCode ?: '') . $this->charCode . ']',
        ]);

        $this->addImportedEmailToTheListing($listing, false);

        if ($andSave) {
            $listing->save();
            $this->hostelID = $listing->id;
            $this->save();
        }

        return $listing;
    }

    public function addImportedEmailToTheListing($listing = null, $andSave = true): void
    {
        if (! $listing) {
            $listing = $this->listing;
        }
        if (! $listing || $this->email === '') {
            return;
        }
        $emailField = $this->getImportSystem()->listingFieldForImportedEmails;
        if ($emailField === '') {
            return;
        }
        $importedEmail = mb_strtolower($this->email);
        if (! in_array($importedEmail, $listing->$emailField)) {
            $temp = $listing->$emailField;
            $temp[] = $importedEmail;
            $listing->$emailField = $temp;
            if ($andSave) {
                $listing->save();
            }
        }
    }

    public function downloadPics($isForce = false): void
    {
        $isDownloaded = DownloadPicsImported::create($this, $isForce)->execute();

        if ($isDownloaded) {
            $this->load('picsObjects');
        }
    }

    public function updateStatus(): void
    {
        try {
            $isActive = $this->importService::isActive($this);
        } catch (\Exception $e) {
            Log::channel('import')->error('updateStatus for importId=' . $this->id, ['exception' => $e]);

            return;
        }

        $this->status = $isActive ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
        if ($this->status === $this->getOriginal('status')) {
            return;
        }

        $this->availability = $isActive;
        $this->save();
    }

    public function updateData(): void
    {
        $this->importService::updateDataForImported($this);
    }

    // Update attached text for the imported record (description, location, reviews, conditions).
    // $attachments - array of [type] => [ [language] => text, [language] => text ]
    // Note: If a type of text isn't in the array (e.j. if there is no 'reviews' element), any existing text of that type are left as is.

    public function updateAttachedTexts(array $attachments): void
    {
        foreach ($attachments as $attachmentType => $attachedData) {
            // Remove duplicates
            foreach ($attachedData as $lang => $text) {
                $others = $attachedData;
                unset($others[$lang]);
                $match = array_search($text, $others);

                if ($match) {
                    // Always keep at least the English text, but delete any other dups.
                    if ($match !== 'en') {
                        unset($attachedData[$match]);
                    }
                    if ($lang !== 'en') {
                        unset($attachedData[$lang]);
                    }
                }
            }

            // echo "$attachmentType: ".implode(', ',array_keys($attachedData)).'. ';
            AttachedText::replaceAllLanguages('imported', $this->id, null, $attachmentType, $this->system, $attachedData, true, false);
        }
    }

    public function staticLink($label)
    {
        return $this->importService::getStaticLinkRedirect(
            $this->urlLink,
            $label,
            $this->id
        );
    }

    /* Accessors & Mutators */

    protected function importSystem(): Attribute
    {
        return Attribute::make(
            get: fn () => ImportSystems::findByName($this->system),
        );
    }

    protected function importService(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->importSystem->getSystemService(),
        );
    }

    public function getRatingAttribute($value)
    {
        return $value === '' ? '' : unserialize($value);
    }

    public function setRatingAttribute($value): void
    {
        $this->attributes['rating'] = ($value ? serialize($value) : '');
    }

    public function getFeaturesAttribute($value)
    {
        return $value === '' ? [] : json_decode($value, true);
    }

    public function setFeaturesAttribute($value): void
    {
        $this->attributes['features'] = ($value !== '' ? json_encode($value) : '');
    }

    public function prunable(): Builder
    {
        return static::where('status', static::STATUS_INACTIVE)->where('updated_at', '<=', now()->subYear());
    }

    /* Relationships */

    public function listing(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Listing\Listing::class, 'hostelID');
    }

    // Note: Can't call it "pics" because there is a pics database field on this table.
    public function picsObjects(): HasMany
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')
            ->where('subjectType', 'imported')
            ->orderBy('picNum');
    }

    public function attachedTexts(): HasMany
    {
        return $this->hasMany(\App\Models\AttachedText::class, 'subjectID')
            ->where('subjectType', 'imported');
    }
}
