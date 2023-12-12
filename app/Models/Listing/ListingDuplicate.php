<?php

namespace App\Models\Listing;

use App\Helpers\EventLog;
use App\Models\Imported;
use App\Models\Review;
use Exception;
use Lib\BaseModel;

class ListingDuplicate extends BaseModel
{
    protected $table = 'duplicates';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $statusOptions = ['flagged', 'hold', 'nonduplicates', 'suspected'];

    public static $maxChoiceDifficultyOptions = [1, 2, 3];

    /*
        Note: 'okToAutoMerge' is only needed for 'choose' mergeType fields
        (or if we eventually have any other merge type that allows multiple choices).

        'choiceDifficulty' - On a scale of 0-2, how complicated is it for staff to make choices for that type of field.
    */

    public static $listingFieldsMergeInfo = [
        'id' => ['mergeType' => 'min'],
        'name' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 1],
        'verified' => ['mergeType' => 'special', 'choiceDifficulty' => 3 /* can sometimes require a choice */],
        'contactStatus' => ['mergeType' => 'max'],
        'roomTypes' => ['mergeType' => 'ignore'],
        'propertyType' => ['mergeType' => 'special'],
        'propertyTypeVerified' => ['mergeType' => 'max'],
        'continent' => ['mergeType' => 'onlyOne'],
        'country' => ['mergeType' => 'onlyOne'],
        'region' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 3],
        'city' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 3],
        'cityAlt' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 2],
        'address' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 1],
        'mapAddress' => ['mergeType' => 'choose', 'okToAutoMerge' => true, 'choiceDifficulty' => 1],
        'zipcode' => ['mergeType' => 'choose', 'okToAutoMerge' => true, 'choiceDifficulty' => 1],
        'poBox' => ['mergeType' => 'choose', 'okToAutoMerge' => true, 'choiceDifficulty' => 1],
        'mailingAddress' => ['mergeType' => 'primaryNonEmpty'],
        'supportEmail' => ['mergeType' => 'combineUniqueElementsOfArrays'],
        'managerEmail' => ['mergeType' => 'combineUniqueElementsOfArrays'],
        'bookingsEmail' => ['mergeType' => 'combineUniqueElementsOfArrays'],
        'importedEmail' => ['mergeType' => 'combineUniqueElementsOfArrays'],
        'invalidEmails' => ['mergeType' => 'combineUniqueElementsOfArrays'],
        'ownerName' => ['mergeType' => 'choose', 'okToAutoMerge' => true],
        'web' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 1],
        'webDomain' => ['mergeType' => 'ignore'],
        'webDisplay' => ['mergeType' => 'precedenceArray', 'mergePrecedence' => [0, 1, -1]],
        'webStatus' => ['mergeType' => 'ignore'],
        'tel' => ['mergeType' => 'choose', 'okToAutoMerge' => true, 'choiceDifficulty' => 1],
        'fax' => ['mergeType' => 'choose', 'okToAutoMerge' => true, 'choiceDifficulty' => 1],
        'videoEmbedHTML' => ['mergeType' => 'primaryNonEmpty'],
        'videoURL' => ['mergeType' => 'primaryNonEmpty'],
        'mgmtFeatures' => ['mergeType' => 'special'],
        'mgmtBacklink' => ['mergeType' => 'primaryNonEmpty'],
        'compiledFeatures' => ['mergeType' => 'primaryNonEmpty'], /* doesn't really matter, will get recompiled by updateListing() anyway */
        'lastUpdate' => ['mergeType' => 'setToNull'],
        'lastUpdated' => ['mergeType' => 'setToNull'],
        'specialNote' => ['mergeType' => 'choose', 'okToAutoMerge' => false, 'choiceDifficulty' => 2],
        'dateAdded' => ['mergeType' => 'earliestNonNullDate'],
        'comment' => ['mergeType' => 'mergeText'],
        'featuredListingPriority' => ['mergeType' => 'max'],
        'boutiqueHostel' => ['mergeType' => 'max'],
        'onlineReservations' => ['mergeType' => 'precedenceArray', 'mergePrecedence' => [0, 1, -1]],
        'unavailableCount' => ['mergeType' => 'setToZero'],
        'preferredBooking' => ['mergeType' => 'max'],
        'ourBookingSystem' => ['mergeType' => 'precedenceArray', 'mergePrecedence' => ['', 'alreadyOther', 'signed-up', 'inactive', 'active']],
        'lastEditSessionID' => ['mergeType' => 'primaryNonEmpty'],
        'ownerLatitude' => ['mergeType' => 'anyNonZero'],
        'ownerLongitude' => ['mergeType' => 'anyNonZero'],
        'geocodingLocked' => ['mergeType' => 'anyNonZero'],
        'latitude' => ['mergeType' => 'special'],
        'longitude' => ['mergeType' => 'special'],
        'locationStatus' => ['mergeType' => 'setToZero'],
        'privatePrice' => ['mergeType' => 'max'],
        'sharedPrice' => ['mergeType' => 'max'],
        'combinedRating' => ['mergeType' => 'ignore'],
        'combinedRatingCount' => ['mergeType' => 'ignore'],
        'contentScores' => ['mergeType' => 'primaryNonEmpty'],
        'overallContentScore' => ['mergeType' => 'max'],
        'source' => ['mergeType' => 'mergeStrings'],
        'targetListing' => ['mergeType' => 'setToZero'],
        'stickerStatus' => ['mergeType' => 'primaryNonEmpty'],
        'stickerPlacement' => ['mergeType' => 'primaryNonEmpty'],
        'stickerDate' => ['mergeType' => 'latestNonZeroDate'],
        'panoramaStatus' => ['mergeType' => 'primaryNonEmpty'],
    ];

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'propertyType' => [
                        'type' => 'select',
                        'options' => Listing::propertyTypes(),
                        'searchQuery' => function ($parameters): void {
                            if (! empty($parameters->inputData['propertyType'])) {
                                $parameters->query
                                    ->leftJoin('listings', 'listingID', '=', 'listings.id')
                                    ->where('listings.propertyType', $parameters->inputData['propertyType']);
                            }
                        },
                    ],
                    'listingID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('listingID') == 'display')
                                && $model->listing ? $model->listing->fullDisplayName() : $model->listingID;
                        }, ],
                    'otherListing' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('otherListing') == 'display')
                                && $model->otherListingListing ? $model->otherListingListing->fullDisplayName() : $model->otherListing;
                        }, ],
                    'source' => ['maxLength' => 100, 'type' => ''],
                    'priorityLevel' => ['searchType' => 'minMax', 'type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'maxChoiceDifficulty' => ['searchType' => 'select', 'type' => 'display', 'options' => self::$maxChoiceDifficultyOptions, 'optionsDisplay' => 'translate'],

                    'score' => ['searchType' => 'minMax', 'type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'notes' => ['type' => 'textarea'],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    public static function insertOrUpdate($listingIDs, $status, $otherValues)
    {
        $output = '';
        $listingIDs = array_unique($listingIDs);
        sort($listingIDs); // Note: listingID is always < otherListing

        foreach ($listingIDs as $idKey1 => $id1) {
            foreach ($listingIDs as $idKey2 => $id2) {
                if ($idKey2 <= $idKey1) {
                    continue;
                } // skip the ones we've already done

                $values = array_merge(['listingID' => $id1, 'otherListing' => $id2, 'status' => $status], $otherValues);

                $existing = self::where('listingID', $id1)->where('otherListing', $id2)->first();
                if ($existing) {
                    // We over-write the existing one if we're setting the status to anything other than suspected,
                    // or if the existing one was merely suspected (and we're updating the score or maybe changing the status).
                    if ($status != 'suspected' || $existing->status == 'suspected') {
                        $existing->fill($values);
                        $existing->setPriorityLevel();
                        $existing->save();
                    }
                } else {
                    $new = new static($values);
                    $new->setPriorityLevel();
                    $new->save();
                }
            }
        }

        return $output;
    }

    /*
    	$skipKnownDuplicates - True to ignore duplicates that were previously found (faster), or false to re-evaluate all of them (except ones manually marked as 'nonduplicates', 'flagged', or 'hold').
    	$minScore - Don't even bother recording the listings as potential duplicates if they have less than $minScore correlation scoring.
    	$didAutoMerge - Gets set to true if auto merged the listing with another listing.
    */

    public static function findDuplicates($listing, $attemptAutoMerging = true, $skipKnownDuplicates = true, $minScore = 72, &$didAutoMerge = null)
    {
        $output = '';
        $didAutoMerge = false;

        $query = Listing::where(function ($query) use ($listing): void {
            $query->where('city', $listing->city)->orWhere('cityAlt', $listing->city)->orWhere('name', $listing->name)->orWhere('address', $listing->address);
        });

        // We limit it to the same country (unless no continent -> means likely wrong country name), or if country is set to UK
        if ($listing->continent != '' && $listing->country != 'United Kingdom') {
            $query->where('country', $listing->country);
        }

        $searchQueries = [
            // (Just one query for now, but could add other queries to also search by.)
            $query,
        ];

        foreach ($searchQueries as $searchQuery) {
            $searchQuery->select('listings.*')->where('listings.id', '!=', $listing->id)->
                areNotListingCorrection()->
                leftJoin('duplicates', function ($join) use ($listing): void {
                    $join->on('listings.id', '=', 'duplicates.listingID')->where('duplicates.otherListing', '=', $listing->id)->
                        orOn('listings.id', '=', 'duplicates.otherListing')->where('duplicates.listingID', '=', $listing->id);
                });

            if ($skipKnownDuplicates) {
                $searchQuery->whereNull('duplicates.id');
            } // skip all that we already have in the database at all
            else {
                // Only skip ones that are merely 'suspected' (keeps existing for any that are 'nonduplicates', 'hold', etc.)
                $searchQuery->where(function ($query): void {
                    $query->whereNull('duplicates.id')->orWhere('duplicates.status', 'suspected');
                });
            }

            $results = $searchQuery->get();

            foreach ($results as $result) {
                $result->listingMaintenance()->addressFix();

                $score = self::calculateSimilarity($listing, $result);
                debugOutput("calculateSimilarity($result->id '$result->name') -> $score");

                if ($score >= $minScore) {
                    $output .= "[possible duplicate ($score%): $result->id $result->name] ";

                    // This may automatically merge them if $attemptAutoMerging, otherwise just computes $maxChoiceDifficulty
                    $didMerge = self::automaticMerging([$listing, $result], $attemptAutoMerging, $autoMergeOutput, $maxChoiceDifficulty);

                    if ($attemptAutoMerging) {
                        $output .= ' Attempt Auto Merge: ' . $autoMergeOutput . ' ';
                        if ($didMerge) {
                            $didAutoMerge = true;
                            $listing = $listing->fresh(); // reload it
                            if (! $listing) {
                                return $output;
                            } // this listing no longer exists because it was merged into another

                            // merging one could have effected our other results, so start over.
                            return $output .
                                self::findDuplicates($listing, $attemptAutoMerging, $skipKnownDuplicates, $minScore); // (note: no reason to pass $didAutoMerge this time)
                        }
                    }
                    self::insertOrUpdate(
                        [$listing->id, $result->id],
                        'suspected',
                        ['source' => 'findListingDuplicates', 'score' => $score, 'maxChoiceDifficulty' => $maxChoiceDifficulty]
                    );
                }
            }
        }

        return $output;
    }

    // assumes addresses have already been fixed with addressFix() (for more uniformity).
    // This function doesn't need the listing objects to have been saved to the database in order to compare them.

    public static function calculateSimilarity($listing1, $listing2, $verbose = false)
    {
        $similarities = [
            'name' => [
                'score' => self::ourStringSimilarityPercent($listing1->name, $listing2->name),
                'weight' => 15,
            ],
            'city' => [
                'score' => self::ourStringSimilarityPercent($listing1->city, $listing2->city),
                'weight' => 50,
            ],
        ];

        if ($similarities['name']['score'] < 35 && $similarities['city']['score'] < 35) {
            return false;
        } // match unlikely, terminate early

        // Address #
        $score = self::compareStringNumbers($listing1->address, $listing2->address, 2);
        $similarities['address numbers'] = [
            'score' => $score > 0 ? ($score + 50) / 1.5 : 0, // we curve upwards the score for any score>0
            'weight' => ($score === false ? 0 : 30), // returned false if one or both don't have numbers
        ];

        // Address
        $insignificantAddressStrings = ['C. ', 'Avenue', 'Avenida', 'Calle', 'Street', 'Road', 'Ave.', 'Str.', 'Highway', 'Rua ', 'Rue ', '#', '.', ',',
            'No.', 'Number', 'nº', /* this one becomes just 'n' when converted to ascii, but that's ok (we compare in both ascii and the original form). */
            '/', '-', 'strasse', 'u', 'ut', ];
        if ($listing1->city != '') {
            $insignificantAddressStrings[] = $listing1->city;
        }
        if ($listing2->city != '') {
            $insignificantAddressStrings[] = $listing2->city;
        }
        if ($listing1->cityAlt != '') {
            $insignificantAddressStrings[] = $listing1->cityAlt;
        }
        if ($listing2->cityAlt != '') {
            $insignificantAddressStrings[] = $listing2->cityAlt;
        }
        if ($listing1->region != '') {
            $insignificantAddressStrings[] = $listing1->region;
        }
        if ($listing2->region != '') {
            $insignificantAddressStrings[] = $listing2->region;
        }
        $insignificantAddressStrings = array_map('mb_strtolower', $insignificantAddressStrings);
        $addressString1 = trim(preg_replace('/\s\s+/', ' ', str_replace($insignificantAddressStrings, ' ', $listing1->address . ' ' . $listing1->cityAlt . ' ' . $listing1->zipcode)));
        $addressString2 = trim(preg_replace('/\s\s+/', ' ', str_replace($insignificantAddressStrings, ' ', $listing2->address . ' ' . $listing2->cityAlt . ' ' . $listing2->zipcode)));
        $similarities['address'] = [
            'score' => self::ourStringSimilarityPercent($addressString1, $addressString2),
            'weight' => 55,
        ];

        // replacedName
        // Note: Sub-strings of other strings should go later in the list, after the super-string of it.
        $insignificantNameStrings = ['B&B', 'B & B', 'BandB', 'Bed & Breakfast', 'Guest House', 'Guesthouse', 'The ', 'Hostel', 'backpackers', 'backpacker', 'Hostal', 'Camping', 'Campground', 'Campsite', 'Hotel', 'International', 'Residency', 'residence', 'pensione', 'pension', 'HI - ', 'YHA', 'Pousada', 'restaurant', 'boutique', ' & ', ' and ', 'Appartamento', 'apartmani', 'apartamentos', 'apartamento', 'apartments', 'apartment', 'apartament', 'appartament', 'accommodation', 'deluxe room', 'rooms', 'youth', 'suites', 'boarding house', 'bunkhouse', 'riad', 'Jugendherberge', 'Jugend', 'Vandrehjem'];
        if ($listing1->city != '') {
            $insignificantNameStrings[] = $listing1->city;
        }
        if ($listing2->city != '') {
            $insignificantNameStrings[] = $listing2->city;
        }
        if ($listing1->cityAlt != '') {
            $insignificantNameStrings[] = $listing1->cityAlt;
        }
        if ($listing2->cityAlt != '') {
            $insignificantNameStrings[] = $listing2->cityAlt;
        }
        $replacedName1 = $listing1->name;
        $replacedName2 = $listing2->name;
        foreach ($insignificantNameStrings as $insignificantString) {
            $replacedName1 = wholeWordStringReplace($insignificantString, ' ', $replacedName1);
            $replacedName2 = wholeWordStringReplace($insignificantString, ' ', $replacedName2);
        }
        // Remove extra spaces
        $replacedName1 = mb_trim(preg_replace('/\s\s+/', ' ', $replacedName1));
        $replacedName2 = mb_trim(preg_replace('/\s\s+/', ' ', $replacedName2));
        if (strlen($replacedName1) > 2 && strlen($replacedName2) > 2) {
            $similarities['name replaced'] = [
                'score' => self::ourStringSimilarityPercent($replacedName1, $replacedName2),
                'weight' => 85,
            ];
        } else { // One or both names have no significant words
            $similarities['name replaced'] = [
                'score' => 30,  // (unknown if it's still a match, but somewhat unlikely)
                'weight' => 10, // low weight since we don't really know much
            ];
            $similarities['name']['weight'] = 70; // give the un-replaced name more weight
        }

        // Telephone
        // Note: Could also use https://github.com/giggsey/libphonenumber-for-php.
        $tel1 = stringDigits($listing1->tel);
        $tel2 = stringDigits($listing2->tel);
        if (strlen($tel1) > 4 && strlen($tel2) > 4) {
            $score = stringSimilarityPercent($tel1, $tel2);
            if ($score < 75) {
                $score /= 2;
            } // if not a close match, it's probably not the same #
            $similarities['tel digits'] = [
                'score' => $score,
                'weight' => 15,
            ];
        }

        $totalScore = $totalWeight = 0;
        foreach ($similarities as $name => $similarity) {
            $totalScore += $similarity['score'] * $similarity['weight'];
            $totalWeight += $similarity['weight'];
        }

        $overallScore = round($totalScore / $totalWeight);

        if ($verbose) {
            print_r($similarities);
            echo "\n\n[Overall Score: $overallScore]\n\n";
        }

        return $overallScore;
    }

    private static function ourStringSimilarityPercent($s1, $s2)
    {
        // Checking for similar words is probably the best method for finding matches,
        // but sometimes the names are spelled just slightly different, so then similar letters can be useful.
        // This uses both methods and returns an average of the two.
        $lettersSimilarity = stringSimilarityPercent($s1, $s2);
        $wordsSimilarity = stringSimilarWordsPercent($s1, $s2);
        $result = array_average([$lettersSimilarity, $wordsSimilarity]);

        return $result;
    }

    /*  This may automatically merge them if $doMergeIfPossible, otherwise just computes $maxChoiceDifficulty
    Returns true/false depending on whether it successfully merged the listings. */

    public static function automaticMerging($listings, $doMergeIfPossible, &$output = '', &$maxChoiceDifficulty = null)
    {
        $output = '';
        $maxChoiceDifficulty = 0;
        $choiceDifficulties = [];
        $canAutoMerge = true;

        if (count($listings) < 2) {
            throw new Exception('AutoMerge attempted for less than two listings.');
        }

        $listingIDs = objectArrayColumn($listings, 'id');

        $choices = self::generateMergeChoices($listings);
        if (! $choices) {
            return false;
        } // some kind of error occurred with generateMergeChoices()

        $chosenValues = [];

        foreach ($choices as $fieldName => $choice) {
            if (! is_array($choice['choices'])) {
                throw new Exception("$fieldName 'choices' is supposed to be an array.");
            }
            if (count($choice['choices']) > 1) {
                if ($choice['mergeType'] == 'onlyOne') {
                    $output .= "[$fieldName values must match to merge these listings.] ";
                    $choiceDifficulties[] = 3;
                } elseif ($choice['okToAutoMerge']) {
                    if (! isset($choice['default'])) {
                        throw new Exception("Default value missing for $fieldName.");
                    }
                    $chosenValues[$fieldName] = $choice['default'];
                } else {
                    // Note ok to auto merge this field... unless we can coalesce them so there's virtually no noice to make...
                    $coalesced = self::tryToCoalesceAutoMergeChoices($fieldName, $choices);
                    if ($coalesced !== false) {
                        $output .= "[$fieldName coalesced choice: $coalesced] ";
                        $chosenValues[$fieldName] = $coalesced;

                        continue;
                    } else {
                        $output .= "[can't automatically merge $fieldName] ";
                        $choiceDifficulties[] = self::$listingFieldsMergeInfo[$fieldName]['choiceDifficulty'];
                    }
                }
            } elseif (count($choice['choices']) == 1) {
                $chosenValues[$fieldName] = reset($choice['choices']);
            }
        }

        // * Other Checks... *

        // Check if multiple listings have paid reviews
        if (self::multipleListingsHaveReviews($listingIDs)) {
            $output .= ' (no - multiple reviews)';
            $choiceDifficulties[] = 3;
        }

        // Check to see if multiple listings are active in the same imported system (could indicate non-duplicates)
        if (self::multipleListingsHaveSameImportedSystem($listingIDs)) {
            $output .= ' [no - multiple booking systems.]';
            $choiceDifficulties[] = 3;
        }

        if ($doMergeIfPossible && count($choiceDifficulties) == 0) {
            $mergeListingsOutput = self::mergeListings($listings, $chosenValues);
            if ($mergeListingsOutput == false) {
                return false;
            }
            EventLog::log('system', 'merge', 'Listing', $chosenValues['id'], implode(',', $listingIDs), $mergeListingsOutput);
            $output .= $mergeListingsOutput;

            return true;
        } else {
            $maxChoiceDifficulty = $choiceDifficulties ? max($choiceDifficulties) : 1;
            if ($maxChoiceDifficulty < 1) {
                $maxChoiceDifficulty = 1;
            } // the minimum of the difficulty levels is 1
            $output .= "[can't automatically merge, maxChoiceDifficulty:$maxChoiceDifficulty]";

            return false;
        }
    }

    /*
        Returns an array of merge choices:
            propertyName => [
                'choices' => [ (array of possible values) ],
                'default' => (default value) (optional),
                'okToAutoMerge' => true/false (optional)
            ]
    */

    // this also does addressFix() on each listing
    public static function generateMergeChoices($listings)
    {
        $mergeInfoFields = self::$listingFieldsMergeInfo;

        // ** Pick Primary Listing ($primary) **

        $listingCorrectionKey = false;
        $hasRemoved = false;
        $primary = 0;
        $maxActiveImported = 0;
        $maxVerified = null;

        foreach ($listings as $key => $listing) {
            $listing->listingMaintenance()->addressFix(); // We also fix the address here in case it wasn't already.

            if ($listing->verified == Listing::$statusOptions['unlisted'] || $listing->verified == Listing::$statusOptions['removed']) {
                $hasRemoved = true;
            } elseif ($listing->isListingCorrection()) {
                $primary = $listingCorrectionKey = $key; // listing corrections take priority over all else
            }
            if (! $listingCorrectionKey) {
                $importedCount = $listing->activeImporteds->count();
                if ($importedCount > $maxActiveImported) { // More active imported takes top priority
                    $maxActiveImported = $importedCount;
                    $primary = $key;
                }
                // Higher verified value is a secondary factor
                if ($listing->verified > $maxVerified || $maxVerified === null) {
                    $maxVerified = $listing->verified;
                    if ($importedCount >= $maxActiveImported) {
                        $primary = $key;
                    }
                }
            }
        }

        if ($hasRemoved && ! $listingCorrectionKey) {
            $mergeInfoFields['verified']['mergeType'] = 'choose';
            $mergeInfoFields['verified']['okToAutoMerge'] = false;
        }

        // ** Create $fields of Listing Fields Grouped by Field **

        $fields = [];
        foreach ($listings as $listingKey => $listing) {
            foreach ($mergeInfoFields as $fieldName => $mergeInfo) {
                $fields[$fieldName][$listingKey] = $listing->$fieldName;
            }
        }

        // ** Comparison **

        $mergeChoices = [];
        foreach ($fields as $field => $values) {
            switch ($mergeInfoFields[$field]['mergeType']) {
                case 'ignore':
                    break;

                case 'setToEmpty':
                    $mergeChoices[$field] = ['choices' => ['']];

                    break;

                case 'setToNull':
                    $mergeChoices[$field] = ['choices' => [null]];

                    break;

                case 'setToZero':
                    $mergeChoices[$field] = ['choices' => ['0']];

                    break;

                case 'primaryNonEmpty':
                    if (! empty($values[$primary])) {
                        $mergeChoices[$field] = ['choices' => [$values[$primary]]];
                    } else {
                        foreach ($values as $value) {
                            if (! empty($value)) {
                                $mergeChoices[$field] = ['choices' => [$value]];

                                break;
                            }
                        }
                    }

                    break;

                case 'choose':
                    if (! isset($mergeInfoFields[$field]['okToAutoMerge'])) {
                        throw new Exception("okToAutoMerge not defined for $field.");
                    }
                    // (no break here)
                    // no break
                case 'onlyOne':	// onlyOne acts as a "choose", but staffListingMerge.html will look for multiple values and issue a warning - TO DO: Handle this differently?

                    $defaultValue = '';
                    if ($listingCorrectionKey) {
                        $defaultValue = $values[$listingCorrectionKey];
                    } // use listing correction values first

                    $specialPreferred = ['free', 'Free']; // specially preferred values (free is preferred over just Available)
                    $resultValueArray = [];
                    foreach ($values as $value) {
                        if ($value != '' && ! in_array($value, $resultValueArray)) {
                            $resultValueArray[] = $value;
                        }
                        if (in_array($value, $specialPreferred) && $defaultValue == '') {
                            $defaultValue = $value;
                        }
                    }

                    // Choose the default value

                    // Special default values for some fields
                    switch ($field) {
                        case 'web':
                            // Disliked website domains
                            $dislikedWebsiteDomains = ['hihostels.com'];
                            $maxWebsiteStatus = null;
                            $bestWebsite = '';
                            foreach ($values as $key => $website) {
                                if ($website == '') {
                                    continue;
                                }
                                $webStatus = $fields['webStatus'][$key];
                                $domain = $fields['webDomain'][$key];
                                if ($domain != '' && in_array($domain, $dislikedWebsiteDomains)) {
                                    $webStatus -= 0.2;
                                } // slight disadvantage for certain domains
                                if ($website == $values[$primary]) {
                                    $webStatus += 0.1;
                                } // very slight advantage for the primary listing
                                if ($maxWebsiteStatus === null || $webStatus > $maxWebsiteStatus) {
                                    $maxWebsiteStatus = $webStatus;
                                    $bestWebsite = $website;
                                }
                            }
                            $defaultValue = $bestWebsite;

                            break;
                    }
                    // use the primary listing's value (except name, which we use the longest value so it keeps things like the city name)
                    if ($defaultValue == '' && $field != 'name') {
                        $defaultValue = $values[$primary];
                    }
                    // if that's also empty, use the longest string
                    if ($defaultValue == '') {
                        $defaultValue = longestString($resultValueArray);
                    }

                    $mergeChoices[$field] = [
                        'choices' => $resultValueArray, 'default' => $defaultValue,
                        'okToAutoMerge' => isset($mergeInfoFields[$field]['okToAutoMerge']) ? true : false,
                        'mergeType' => $mergeInfoFields[$field]['mergeType'],
                    ];

                    break;

                case 'mergeText':
                    $resultValue = '';
                    foreach ($values as $value) {
                        if ($value == '' || $value == $resultValue) {
                            continue;
                        }
                        if ($resultValue) {
                            $resultValue .= "\n\n";
                        }
                        $resultValue .= $value;
                    }
                    $mergeChoices[$field] = ['choices' => [$resultValue]];

                    break;

                case 'mergeStrings':
                    $resultValue = '';
                    foreach ($values as $value) {
                        if ($value == '' || $value == $resultValue) {
                            continue;
                        }
                        if ($resultValue != '') {
                            $resultValue .= ', ';
                        }
                        $resultValue .= $value;
                    }
                    $mergeChoices[$field] = ['choices' => [$resultValue]];

                    break;

                case 'add':
                    $resultValue = 0;
                    foreach ($values as $value) {
                        $resultValue += $value;
                    }
                    $mergeChoices[$field] = ['choices' => [$resultValue]];

                    break;

                case 'max':
                    $resultValue = $values[0];
                    foreach ($values as $value) {
                        if ($value > $resultValue) {
                            $resultValue = $value;
                        }
                    }
                    $mergeChoices[$field] = ['choices' => [$resultValue]];

                    break;

                case 'min':
                    $resultValue = $values[0];
                    foreach ($values as $value) {
                        if ($value < $resultValue) {
                            $resultValue = $value;
                        }
                    }
                    $mergeChoices[$field] = ['choices' => [$resultValue]];

                    break;

                case 'earliestNonNullDate':
                    $earliestTime = null;
                    foreach ($values as $value) {
                        if ($value == null || $value == '') {
                            continue;
                        }
                        if ($earliestTime == null || $value < $earliestTime) {
                            $earliestTime = $value;
                        }
                    }
                    if ($earliestTime !== null) {
                        $mergeChoices[$field] = ['choices' => [$earliestTime]];
                    }

                    break;

                case 'latestNonZeroDate':
                    $latestTime = null;
                    foreach ($values as $value) {
                        if ($value == null || $value == '') {
                            continue;
                        }
                        if ($latestTime == null || $value > $latestTime) {
                            $latestTime = $value;
                        }
                    }
                    if ($latestTime !== null) {
                        $mergeChoices[$field] = ['choices' => [$latestTime]];
                    }

                    break;

                case 'anyNonZero':
                    $resultValue = 0;
                    foreach ($values as $value) {
                        if ($value != 0) {
                            $resultValue = $value;
                        }
                    }
                    $mergeChoices[$field] = ['choices' => [$resultValue]];

                    break;

                case 'precedenceArray':
                    $bestPrecedence = 0;
                    foreach ($values as $value) {
                        foreach ($mergeInfoFields[$field]['mergePrecedence'] as $precedencePosition => $precedenceValue) {
                            if ($value == $precedenceValue && $precedencePosition > $bestPrecedence) {
                                $bestPrecedence = $precedencePosition;
                            }
                        }
                    }
                    $mergeChoices[$field] = ['choices' => [$mergeInfoFields[$field]['mergePrecedence'][$bestPrecedence]]];

                    break;

                case 'special':
                    switch ($field) {
                        /* we no longer prefer "HI - " names.
    					case 'name':
    						// The only special thing about this is it prefers "HI - " names
    						$resultValueArray = [ ];
    						$primaryName = $values[$primary];
    						foreach ($values as $v) {
    							if ($v != '' && !in_array($v,$resultValueArray)) {
    								$resultValueArray[] = $v;
    								if (strpos($v,'HI - ')===0 && strpos($primaryName,'HI - ')!==0 ) $primaryName = $v;
    							}
    						}
    						if ($listingCorrectionKey) $primaryName = $values[$listingCorrectionKey];
    						$mergeChoices[$field] = [ 'choices' => $resultValueArray, 'default' => $primaryName, 'okToAutoMerge' => false ];
    					    break;
                        */

                        case 'propertyType':
                            $choices = [];
                            $defaultValue = '';
                            $verified = false;
                            $okToAutoMerge = true;
                            foreach ($values as $key => $value) {
                                if ($value != '' && ! in_array($value, $choices)) {
                                    $choices[] = $value;
                                }
                                if ($fields['propertyTypeVerified'][$key]) {
                                    if ($verified && $value != $defaultValue && $defaultValue != '') {
                                        $okToAutoMerge = false; // multiple properties have conflicting verified propertyTypes
                                    } else {
                                        $defaultValue = $value;
                                        $verified = true;
                                    }
                                }
                            }
                            if (! $verified) {
                                // No verified propertyType, so choose the best default value
                                $precidenceArray = ['Other', 'Hostel', 'Hotel', 'Guesthouse', 'Apartment', 'Campsite'];
                                $bestPrecedence = 0;
                                foreach ($values as $value) {
                                    foreach ($precidenceArray as $precedencePosition => $precedenceValue) {
                                        if ($value == $precedenceValue && $precedencePosition > $bestPrecedence) {
                                            $bestPrecedence = $precedencePosition;
                                        }
                                    }
                                }
                                $defaultValue = $precidenceArray[$bestPrecedence];
                            }

                            if ($verified || count($choices) < 2) {
                                $mergeChoices[$field] = ['choices' => [$defaultValue]];
                            } else {
                                $mergeChoices[$field] = [
                                    // Note: If $okToAutoMerge, meaning there weren't multiple verified types, we don't show choices, we just go with the default.
                                    // updateListing() will change the property type as needed anyway.
                                    'choices' => $okToAutoMerge ? [$defaultValue] : $choices,
                                    'default' => $defaultValue, 'okToAutoMerge' => $okToAutoMerge, 'mergeType' => 'choose',
                                ];
                            }

                            break;

                        case 'latitude':
                        case 'longitude':
                            // Set to any listing with geocodingLocked set, or else any nonZero latitude/longitude
                            foreach ($values as $key => $value) {
                                if ($fields['geocodingLocked'][$key]) {
                                    $mergeChoices[$field] = ['choices' => [$value]];

                                    break;
                                }

                                if ($value && empty($mergeChoices[$field])) {
                                    $mergeChoices[$field] = ['choices' => [$value]];
                                }
                            }

                            break;

                        case 'verified':
                            $resultValue = $values[0];
                            foreach ($values as $value) {
                                if ($value > $resultValue) {
                                    $resultValue = $value;
                                }
                            }
                            $mergeChoices[$field] = ['choices' => [$resultValue]];

                            break;

                        case 'mgmtFeatures':
                            // We just merge the features (the primary listing gets precidence if different values for the same feature)
                            $features = $values[$primary];
                            foreach ($values as $key => $otherListingFeatures) {
                                if ($key == $primary) {
                                    continue;
                                }
                                $features = ListingFeatures::merge($features, $otherListingFeatures);
                            }
                            $mergeChoices[$field] = ['choices' => [$features]];

                            break;
                    }

                    break;

                case 'combineUniqueElementsOfArrays':
                    $resultValue = [];
                    foreach ($values as $value) {
                        $resultValue = array_merge($resultValue, $value);
                    }
                    if ($resultValue) {
                        $mergeChoices[$field] = ['choices' => [array_unique($resultValue)]];
                    }

                    break;

                default:
                    throw new Exception('Unknown mergeType ' . $mergeInfoFields[$field]['mergeType']);
            }
        }

        return $mergeChoices;
    }

    /* Returns false if the listings weren't merged, otherwise returns a string describing the merge (for logging/display). */

    public static function mergeListings($listings, $chosenValues, $testMode = false)
    {
        if (count($listings) < 2) {
            return false;
        }

        // ** Set $resultingListing and $otherListings **

        $resultingListing = null;
        $otherListings = [];
        foreach ($listings as $listing) {
            if ($listing->id == $chosenValues['id']) {
                $resultingListing = $listing;
            } else {
                $otherListings[] = $listing;
            }
        }

        if (! $resultingListing) {
            throw new Exception("Choosen ID wasn't in the listings list.");
        } // shouldn't be possible
        if (! $otherListings) {
            throw new Exception('No other listings other than the resulting listing.');
        } // shouldn't be possible

        $changesDescription = '';

        foreach ($chosenValues as $field => $chosenValue) {
            // ** Describe Merge (for logging) **

            $choicesString = '';
            $choicesInTheChoicesString = [];
            $MAX_VALUE_LENGTH = 100;

            foreach ($listings as $listing) {
                $listingValue = $listing->$field;
                if (in_array($listingValue, $choicesInTheChoicesString)) {
                    continue;
                }
                if ($listingValue === '' || $listingValue === []) {
                    continue;
                }
                $listingValueString = (is_array($listingValue) ? json_encode($listingValue) : $listingValue);

                $choicesInTheChoicesString[] = $listingValue;
                $choicesString .= ($choicesString != '' ? ', ' : '') .
                    '["' . mb_strimwidth($listingValueString, 0, $MAX_VALUE_LENGTH, '...') . '"]';
            }

            if (count($choicesInTheChoicesString) > 1) {
                $chosenValueString = (is_array($chosenValue) ? json_encode($chosenValue) : $chosenValue);
                $changesDescription .= ($changesDescription != '' ? ', ' : '') .
                    "$field: $choicesString -> \"" .
                    mb_strimwidth($chosenValueString, 0, $MAX_VALUE_LENGTH, '...') . '"';
            }

            // ** Make the Changes to $resultingListing **

            $resultingListing->$field = $chosenValue;
        }

        // ** Acquire Resources from Other Listings **

        if (! $testMode) {
            foreach ($otherListings as $otherListing) {
                $resultingListing->acquireResourcesFromAnotherListing($otherListing);
                $otherListing = $otherListing->fresh(); // re-load it (to update relationship data before deleting)
                $otherListing->delete();
            }

            $resultingListing->save();
            $resultingListing->clearRelatedPageCaches();
        } else {
            print_r($resultingListing);
            exit();
        }

        return $changesDescription;
    }

    /*
        Tries to find a value that encompasses all of the $choices without losing any valuable information.

        Returns: The text of the resulting choice, otherwise false.
    */

    // Assumes the duplicates table has already been checked, assumes addresses have already been fixed with addressFix().

    private static function tryToCoalesceAutoMergeChoices($fieldName, $choices, $ignorableSet = false)
    {
        if (count($choices) < 2) {
            throw new Exception("Less than 2 choices (shouldn't happen).");
        }

        // * cityWords *
        // (used by some field types but not others, like city)

        $cityWords = [];
        if (is_array($choices['city']['choices'])) {
            $cityWords = array_merge($cityWords, $choices['city']['choices']);
        } else {
            $cityWords[] = $choices['city']['choices'];
        }
        if (is_array($choices['cityAlt']['choices'])) {
            $cityWords = array_merge($cityWords, $choices['cityAlt']['choices']);
        } else {
            $cityWords[] = $choices['cityAlt']['choices'];
        }
        if (is_array($choices['zipcode']['choices'])) {
            $cityWords = array_merge($cityWords, $choices['zipcode']['choices']);
        } else {
            $cityWords[] = $choices['zipcode']['choices'];
        }
        if (is_array($choices['region']['choices'])) {
            $cityWords = array_merge($cityWords, $choices['region']['choices']);
        } else {
            $cityWords[] = $choices['region']['choices'];
        }

        // * Field Specific *

        switch ($fieldName) {
            case 'name':
                $allowExtraWords = false;
                $ignorableSeparatorsForThisField = [];
                if ($ignorableSet) {
                    $ignorableWordsForThisField = $ignorableSet;

                    break;
                }
                // The concept here is all of the choices must have ignorable words from one single set (so "backpackers" is interchangeable with "hostel" but not "hotel", etc.)
                $inAllSets = ['the', 'restaurant', 'bar', 'and', 'e', 'riad', 'el'];
                $ignorableSets = [
                    // Hostels/Hotels/Guesthouses
                    ['hostel', 'hostels', 'backpackers', 'backpacker', 'international', 'yha', 'jh', 'jugendherberge', 'youth', 'bunkhouse', 'hi',
                        'b&b', 'b & b', 'bandb', 'bed & breakfast', 'guest house', 'guesthouse', 'hostal', 'hotel', 'international', 'residency', 'residence', 'pensione', 'pension', 'pousada', 'boutique', 'accommodation', 'deluxe room', 'privates', 'rooms', 'suites', 'boarding house', 'stf', ],
                    // Campsites
                    ['camping', 'campground', 'campsite'],
                    // Apartments
                    ['residency', 'residence', 'pensione', 'pension', 'pousada', 'appartamento', 'apartmani', 'apartamentos', 'apartamento', 'apartments', 'apartment', 'apartament', 'appartament', 'accommodation', 'deluxe room', 'rooms', 'suites', 'boarding house', 'bunkhouse'],
                ];
                foreach ($ignorableSets as $set) {
                    $r = self::tryToCoalesceAutoMergeChoices($fieldName, $choices, array_merge($cityWords, $inAllSets, $set));
                    if ($r !== false) {
                        return $r;
                    }
                }

                return false;

                break;

            case 'address':
                $allowExtraWords = true;
                $ignorableSeparatorsForThisField = ['strasse', 'str.'];
                $ignorableWordsForThisField = array_merge($cityWords, ['c.', 'avenue', 'avenida', 'calle', 'street', 'road', 'ave', 'av', 'str', 'st', 'strasse', 'highway', 'rua', 'rue', 'r', 'no', 'number', 'nº', 'strasse', 'street', 'bvd', 'bv', 's', 'san.', 'cad', 'cd', 'viale', 'apt', 'ap', 'u', 'utca', 'da', 'lane', 'ln']);

                break;

            case 'web':
                $allowExtraWords = true;
                $ignorableSeparatorsForThisField = [];
                $ignorableWordsForThisField = ['http://', 'https://', 'www.yha.org.uk'];

                foreach ($choices[$fieldName]['choices'] as $key => $choice) {
                    if (stripos($choice, 'hihostels.com') !== false) { // Websites that are expendable...
                        $ignorableWordsForThisField[] = $choices[$fieldName]['choices'][$key];
                    }
                }

                break;

            case 'city':
            case 'country':
            case 'cityAlt':
            case 'region':
                $allowExtraWords = false;
                $ignorableSeparatorsForThisField = [];
                $ignorableWordsForThisField = [];

                break;

            case 'specialNote':
            case 'continent':
            case 'verified':
                return false; // these we don't want to automerge at all

            default:
                throw new Exception("Unknown nonAutoMerge field $fieldName");
        }

        $separators = array_merge($ignorableSeparatorsForThisField, [' ', ',', '#', '.', ';', '&', '/', '(', ')', '-', '\'']);
        $ignorables = array_merge($ignorableWordsForThisField, $separators); // Could have other separators, but currently it happens to be the same as the ignorables list.
        $wholeWordSeparators = $ignorableWordsForThisField; // necessary really only for multiple word items so they are parsed together

        $values = $choices[$fieldName]['choices'];

        $exploded = [];
        foreach ($values as $key => $value) {
            $exploded[] = multiExplode($separators, $wholeWordSeparators, $value);
            if ($value === $choices[$fieldName]['default']) {
                $defaultKey = $key;
            }
        }
        self::normalizeBestCapitalization($exploded);

        // Note: By using arrayKeysCase() with simplediff(), arrayKeysCase() may modify the choices by capitalizing, UTF-8 chars, etc one one string based on the other string's use of capitalization and UTF-8 chars.
        $result = self::containsAllOfTheOther($exploded, $defaultKey, $ignorables, $allowExtraWords);

        if ($result === false) {
            return false;
        } else {
            return trim(implode('', $exploded[$result]), ', ');
        }
    }

    // Check if multiple listings have paid reviews

    public static function multipleListingsHaveReviews($listingIDs)
    {
        return Review::whereIn('hostelID', $listingIDs)->where('status', 'publishedReview')->groupBy('hostelID')->get()->count() > 1;
    }

    // Check to see if multiple listings are active in the same imported system (could indicate non-duplicates)

    public static function multipleListingsHaveSameImportedSystem($listingIDs)
    {
        $importeds = Imported::whereIn('hostelID', $listingIDs)->where('status', 'active')->get();
        $multipleInSameSystem = false;
        $listingInSystem = [];
        foreach ($importeds as $imported) {
            if (isset($listingInSystem[$imported->system]) && $listingInSystem[$imported->system] !== $imported->hostelID) {
                return true;
            }
            $listingInSystem[$imported->system] = $imported->hostelID;
        }

        return false;
    }

    /* Utility Functions */

    // (Some of these could be moved to helpers.php if they're useful in other code.)

    // returns a percent 0-100
    public static function compareStringNumbers($s1, $s2, $minNumberLength = 1)
    {
        preg_match_all('/(\d{' . $minNumberLength . ',})/', $s1, $matches);
        $matches1 = $matches[1];
        preg_match_all('/(\d{' . $minNumberLength . ',})/', $s2, $matches);
        $matches2 = $matches[1];
        if (count($matches1) < 1 || count($matches2) < 1) {
            return false;
        }

        $diff1 = array_diff($matches1, $matches2);
        $score1 = (count($matches1) - count($diff1)) / count($matches1);
        $diff2 = array_diff($matches2, $matches1);
        $score2 = (count($matches2) - count($diff2)) / count($matches2);

        return round(100 * ($score1 + $score2) / 2);
    }

    // Normalize capitalization across multiple sets of words by using the best capitalization of matching words from the sets (preferring mixed-case words to all-upper or all-lowercase).

    public static function normalizeBestCapitalization(&$wordSets): void
    {
        foreach ($wordSets as $wordSetKey => $wordSet) {
            foreach ($wordSet as $wordKey => $word) {
                $lowerWord = mb_strtolower($word);
                $upperWord = mb_strtoupper($word);
                $asciiWord = utf8ToAscii($word);
                $asciiLowerWord = strtolower(utf8ToAscii($word));
                $asciiUpperWord = strtoupper(utf8ToAscii($word));

                foreach ($wordSets as $wordSetKey2 => $wordSet2) {
                    if ($wordSetKey2 == $wordSetKey) {
                        continue;
                    }
                    foreach ($wordSet2 as $wordKey2 => $word2) {
                        if ($word2 === $word) {
                            continue;
                        } elseif ($word2 === $asciiLowerWord || $word2 === $asciiWord || $word2 === $lowerWord || $word2 === $upperWord || $word2 === $asciiUpperWord) {
                            $wordSets[$wordSetKey2][$wordKey2] = $word;
                        } // word is the UTF-8/capitalized version in this case
                        elseif (mb_strtolower($word2) === $word || mb_strtoupper($word2) === $word || utf8ToAscii($word2) === $word || strtolower(utf8ToAscii($word2)) === $word || strtoupper(utf8ToAscii($word2)) === $word) {
                            $wordSets[$wordSetKey][$wordKey] = $word = $word2;
                        } // word2 is the UTF-8/capitalized version in this case
                    }
                }
            }
        }
    }

    /*
        For sets of words $wordSets, returns the key of the set that is a superset of all of the other $wordSets sets.
        Defaults to $defaultSetKey.
    */

    public static function containsAllOfTheOther($wordSets, $defaultSetKey = 0, $ignorables = [], $allowExtraWords = true)
    {
        // Make everything lowercase ascii and remove ignorables

        $ignorablesInLowercase = [];
        foreach ($ignorables as $s) {
            $ignorablesInLowercase[] = strtolower($s);
        }

        $ignorablesInLowercaseAscii = [];
        foreach ($ignorables as $s) {
            $ignorablesInLowercaseAscii[] = utf8ToAscii($s);
        }

        // Make everything lowercase ascii and remove ignorables
        foreach ($wordSets as $wordSetKey => $wordSet) {
            foreach ($wordSet as $wordKey => $originalWord) {
                $lowercaseWord = strtolower($originalWord);
                $asciiLowercaseWord = strtolower(utf8ToAscii($originalWord));
                if (in_array($lowercaseWord, $ignorablesInLowercase) || in_array($asciiLowercaseWord, $ignorablesInLowercaseAscii)) {
                    unset($wordSets[$wordSetKey][$wordKey]);
                } else {
                    $wordSets[$wordSetKey][$wordKey] = $asciiLowercaseWord;
                }
            }
        }

        $currentSuperset = $defaultSetKey;
        foreach ($wordSets as $wordSetKey => $wordSet) {
            if ($wordSetKey == $currentSuperset) {
                continue;
            }

            // * Check for Equal Strings or Substring of the Other *
            // (faster to check this first, and finds cases such as same words but different spacing)
            $implodedSuperset = mb_trim(implode('', $wordSets[$currentSuperset]));
            $implodedWordSet = mb_trim(implode('', $wordSet));
            if ($implodedSuperset === $implodedWordSet) {
                continue;
            } // is same string (after ignoring ignorables)
            elseif ($allowExtraWords && $implodedWordSet != '' && $implodedSuperset != '') {
                if (strpos($implodedSuperset, $implodedWordSet) !== false) {
                    continue;
                } // wordSet is a substring of superset
                elseif (strpos($implodedWordSet, $implodedSuperset) !== false) {
                    // superset is a substring of wordset
                    $currentSuperset = $wordSetKey;

                    continue;
                }
            }

            $supersetRemaining = $wordSets[$currentSuperset];
            $hasExtraWords = false;
            foreach ($wordSet as $wordKey => $word) {
                $foundIt = false;
                foreach ($wordSets[$currentSuperset] as $supersetWordKey => $supersetWord) {
                    if ($supersetWord === $word) {
                        unset($supersetRemaining[$supersetWordKey]);
                        $foundIt = true;

                        break;
                    }
                }
                if (! $foundIt) {
                    $hasExtraWords = true;
                    if (! $allowExtraWords) {
                        return false;
                    }
                }
            }
            if (! $allowExtraWords && count($supersetRemaining)) {
                return false;
            }
            if ($hasExtraWords) {
                if (count($supersetRemaining)) {
                    return false;
                } // multiple sets with extra words
                $currentSuperset = $wordSetKey;
            }
        }

        return $currentSuperset;
    }

    public static function dailyMaintenance()
    {
        $output = '';

        // Sanity check: Make sure there are no 0 priorityLevel duplicates.
        if (self::where('priorityLevel', 0)->count()) {
            logError("There shouldn't be any duplicates with 0 priorityLevel!");
        }
        if (self::where('status', 'suspected')->where('maxChoiceDifficulty', 0)->count()) {
            logError("There shouldn't be any 'suspected' duplicates with 0 maxChoiceDifficulty.");
        }

        /* This was the for Kristy - she preferred to have some number assigned each day

        // Assign some duplicates each day to certain users

        $output .= 'Adding Listing Duplicates To Do: ';

        $userToDoPerDay = [
            // userID => # due per day
            41299 => 20
        ];

        $existingCounts = self::where('status', 'suspected')->where('userID', '!=', 0)->
            select(DB::raw('userID, count(*) as countForUser'))->groupBy('userID')->pluck('countForUser', 'userID');

        // Remove all existing assignments
        self::where('status', 'suspected')->where('userID', '!=', 0)->update([ 'userID' => 0 ]);

    	foreach ($userToDoPerDay as $userID => $howManyToDo) {
    	    $output .= "[user $userID: $howManyToDo] ";
    	    $howManyToDo += $existingCounts->get($userID, 0);
    		$baseQuery = self::where('status', 'suspected')->where('userID', 0)->
    		    orderBy('priorityLevel', 'desc')->orderBy('score', 'desc')->limit($howManyToDo);
    		$topDuplicates = with(clone $baseQuery)->where('score', '>=', 83)->pluck('id')->all();
    		if (count($topDuplicates) != $howManyToDo) {
    		    // Not enough found at that minimum score level, try allowing a lower score...
    		    $topDuplicates = with(clone $baseQuery)->where('score', '>=', 75)->pluck('id')->all();
    		}
    		self::whereIn('id', $topDuplicates)->update([ 'userID' => $userID ]);
    	}
    	$output .= "\n";
    	*/

        return $output;
    }

    /* Misc */

    public function setPriorityLevel()
    {
        // Listing doesn't exist
        if (! $this->listing || ! $this->otherListingListing) {
            logError("$this->id has a listing that doesn't exist.");
        }
        // Both live
        elseif ($this->listing->isLive() && $this->otherListingListing->isLive()) {
            $this->priorityLevel = 90;
        }
        // One live and other onlineReservations
        elseif ($this->listing->isLive() && $this->otherListingListing->onlineReservations ||
                $this->otherListingListing->isLive() && $this->listing->onlineReservations) {
            $this->priorityLevel = 80;
        }
        // Both not-live and both onlineReservations
        elseif ($this->listing->onlineReservations && $this->otherListingListing->onlineReservations) {
            $this->priorityLevel = 60;
        }
        // Both not-live and one onlineReservations
        elseif ($this->listing->onlineReservations || $this->otherListingListing->onlineReservations) {
            $this->priorityLevel = 40;
        }
        // Both not-live and both not onlineReservations
        else {
            $this->priorityLevel = 20;
        }

        if ($this->listing->isPrimaryPropertyType() || $this->otherListingListing->isPrimaryPropertyType()) {
            $this->priorityLevel += 5;
        }

        return $this; // for function chaining convenience
    }

    /* Scopes */

    public function scopeForListingID($query, $listingID)
    {
        return $query->where(function ($query) use ($listingID): void {
            $query->where('listingID', '=', $listingID)->orWhere('otherListing', '=', $listingID);
        });
    }

    // Find matches for duplicates between any two listing IDs in $listingIDs.

    public function scopeForListingIDs($query, $listingIDs)
    {
        return $query->whereIn('listingID', $listingIDs)->whereIn('otherListing', $listingIDs);
    }

    /* Relationships */

    public function listing()
    {
        return $this->hasOne(\App\Models\Listing\Listing::class, 'id', 'listingID');
    }

    public function user()
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'userID');
    }

    public function otherListingListing() // TO DO: Rename "otherListing" field to otherListingID, and then rename this function to "otherListing()"
    {
        return $this->hasOne(\App\Models\Listing\Listing::class, 'id', 'otherListing');
    }
}
