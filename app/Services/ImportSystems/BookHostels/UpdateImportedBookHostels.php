<?php

namespace App\Services\ImportSystems\BookHostels;

use App\Models\Imported;
use App\Models\Languages;
use App\Models\Rating;
use App\Services\ImportSystems\Import;
use Illuminate\Support\Facades\Log;

class UpdateImportedBookHostels
{
    public function handle(Imported $imported): void
    {
        $importData = $this->getImportData($imported->intCode);

        $values = ImportBookHostels::getValues($importData);

        $ratingData = $this->getRating($imported);
        if ($ratingData) {
            $values['rating'] = $ratingData;
        }

        $attachments = $this->getAttachments($imported);

        Import::update($imported, $values, $attachments);
    }

    private function getRating($imported): array
    {
        // Note: Existing rating data to the already saved ratings so that we keep the old ones if we aren't able to get new ratings.
        $ratingData = $imported->rating ?: [];

        // Get hostelworld ratings
        $data = APIBookHostels::getImportedRating($imported->intCode);
        if (! empty($data->summary->totalReviews) && ! empty($data->summary->overall)) {
            $ratingData['Hostelworld'] = [
                'count' => (int) $data->summary->totalReviews,
                'overall' => (int) ($data->summary->overall * 10),
                'cleanliness' => (int) ($data->breakdown->CLEANLINESS * 10),
                'staff' => (int) ($data->breakdown->STAFF * 10),
                'location' => (int) ($data->breakdown->LOCATION * 10),
                'atmosphere' => (int) ($data->breakdown->ATMOSPHERE * 10),
                'security' => (int) ($data->breakdown->SECURITY * 10),
            ];
        } elseif (! isset($ratingData['Hostelworld'])) { // Note that if there was a previous successful import of their ratings, we don't overwrite it with 0
            $ratingData['Hostelworld'] = ['count' => 0];
        }

        return $ratingData;
    }

    private function getAttachments($imported): array
    {
        $attachments = [];

        foreach (BookHostelsService::$LANGUAGE_CODES as $theirLanguageCode => $ourLanguageCode) {
            if (! Languages::isCodeUsedOnLiveSite($ourLanguageCode)) {
                continue;
            }

            minDelayBetweenCalls('BookHostels:propertyinformation', 1000); // (miliseconds) we were getting a "quota exceeded" error.
            $request = APIBookHostels::doRequest(
                'propertyinformation',
                ['PropertyNumber' => $imported->intCode, 'Language' => $theirLanguageCode],
                35,
                2
            );
            if (! $request['success']) {
                continue;
            }

            $result = $request['data'];
            if (! isset($result['description'])) {
                Log::channel('import')->warning("BookHostels - No description for $imported->id. Result: " . json_encode($result));

                continue;
            }
            $description = trim($result['description']);
            if ($description !== '') {
                $attachments['description'][$ourLanguageCode] = $description;
            }

            $location = isset($result['directions']) ? trim($result['directions']) : '';
            if ($location !== '') {
                $attachments['location'][$ourLanguageCode] = $location;
            }

            // Note: They also give us the conditions when there is a propertybookingrequest,
            // so we don't really use these saved ones yet, but we save them anyway.
            $conditions = isset($result['conditions']) ? trim($result['conditions']) : '';
            if ($conditions !== '') {
                $attachments['conditions'][$ourLanguageCode] = $conditions;
            }

            // Get reviews
            $request = APIBookHostels::doRequest(
                'propertyreviews',
                [
                    'PropertyNumber' => $imported->intCode,
                    'Language' => $theirLanguageCode,
                    'OrderBy' => 'newest',
                    'LimitResults' => 20,
                    'MonthCount' => 24,
                ],
                35,
                2
            );
            if ($request['success']) {
                foreach ($request['data']['reviews'] as $review) {
                    //$lang = LanguageDetection::detect((string) $review);
                    //if ($lang == '') $lang = 'en';
                    $attachments['reviews'][$ourLanguageCode][] = [
                        'name' => $review['Reviewer'],
                        // 'country' =>
                        'date' => $review['Stayed'],
                        'text' => $review['Notes'],
                        'rating' => Rating::convertPercentToStarRating($review['rating']),
                    ];
                }
            }
        }

        return $attachments;
    }

    public function updateStatus($imported): bool
    {
        return $this->isActiveImport($imported);
    }

    public function isActiveImport(Imported $imported): bool
    {
        $request = APIBookHostels::doRequest(
            'propertyinformation',
            ['PropertyNumber' => $imported->intCode],
            35,
            2
        );
        if (! $request['success'] && self::isInactiveErrorCode($request['error']['code'])) {
            return false;
        }

        $request = APIBookHostels::doRequest(
            'propertyreviews',
            [
                'PropertyNumber' => $imported->intCode,
                'LimitResults' => 1,
                'MonthCount' => 24,
            ],
            35,
            2
        );
        if (! $request['success'] && self::isInactiveErrorCode($request['error']['code'])) {
            return false;
        }

        return true;
    }

    /**
     *  2022: "Property number (PropertyNumber) is not valid property number"
     *  2027: "Specified property is not active"
     *
     * @param int $errorCode
     *
     * @return bool
     */
    public static function isInactiveErrorCode(int $errorCode): bool
    {
        return in_array($errorCode, [2022, 2027]);
    }

    private function getImportData(mixed $intCode): array
    {
        $request = APIBookHostels::doRequest(
            'propertyinformation',
            ['PropertyNumber' => $intCode],
            35,
            2
        );
        if (! $request['success']) {
            return [];
        }

        return $request['data'];
    }
}
