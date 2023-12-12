<?php

namespace App\Services\ImportSystems\Hostelsclub;

use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use Lib\LanguageDetection;

class UpdateImportedHostelsclub
{
    public function handle($imported): void
    {
        $attachments = [];

        foreach (HostelsclubService::$LANGUAGE_MAP as $theirCode => $ourCode) {
            if (! Languages::isCodeUsedOnLiveSite($ourCode)) {
                continue;
            } // skip languages we aren't using on the live site
            if (isset($attachments['description'][$ourCode])) {
                continue;
            } // it's maybe possible they already returned this language when we requested a different one

            $result = APIHostelsclub::doXmlRequest('OTA_HotelDescriptiveInfoRQ', "<HotelDescriptiveInfos><HotelDescriptiveInfo HotelCode=\"$imported->intCode\">" .
                                                                       '<HotelInfo SendData="true"/><Policies SendPolicies="true"/>' .
                                                                       ($ourCode === 'en' ? '<MultimediaObjects SendData="true"/>' : '') .
                                                                       '<ContentInfos><ContentInfo Name="CustomerRatings"/>' .
                                                                       // (review data is the same regardless of language, so we just use the English results)
                                                                       ($ourCode === 'en' ? '<ContentInfo Name="CustomerReviews"/>' : '') .
                                                                       '</ContentInfos></HotelDescriptiveInfo></HotelDescriptiveInfos>', $theirCode, '', 30, 2);

            if (! isset($result->HotelDescriptiveContents)) {
                /* logWarning("No description returned for $imported->id language $theirCode."); */
                continue;
            }

            if ($ourCode === 'en') {
                // Get ratings
                $ratings = $result->HotelDescriptiveContents->HotelDescriptiveContent->TPA_Extensions->CustomerRatings->Rating;
                if ($ratings) {
                    $ratingNameMap = ['overall' => 'overall', 'cleanliness' => 'cleanliness', 'staff' => 'staff', 'position' => 'location', 'personality' => 'atmosphere'];
                    $ratingData = ['count' => (string) $ratings[0]->attributes()->NumberRatings];
                    foreach ($ratings as $rating) {
                        if (! isset($ratingNameMap[(string) $rating->attributes()->Type])) {
                            continue;
                        }
                        $ratingData[$ratingNameMap[(string) $rating->attributes()->Type]] = (string) $rating->attributes()->Value;
                    }
                    $imported->rating = $ratingData;
                }

                // We only retrieve the $reviewResults once because it's the same data for all languages
                $reviews = $result->HotelDescriptiveContents->HotelDescriptiveContent->TPA_Extensions->CustomerReviews;
                if ($reviews->Review) {
                    foreach ($reviews->Review as $review) {
                        $lang = LanguageDetection::detect((string) $review);
                        if ($lang === '') {
                            $lang = 'en';
                        }
                        $attachments['reviews'][$lang][] = [
                            'name' => (string) $review->attributes()->CustomerName,
                            'country' => (string) $review->attributes()->CustomerNationality,
                            'date' => trim(substr((string) $review->attributes()->Date, 0, 10)),
                            'text' => (string) $review,
                            'rating' => (string) $review->attributes()->Rating,
                        ];
                    }
                }

                // Latitude/Longitude
                if ($result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Position) {
                    $attributes = $result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Position->attributes();
                    $imported->latitude = round((float) $attributes['Latitude'], Listing::LATLONG_PRECISION);
                    $imported->longitude = round((float) $attributes['Longitude'], Listing::LATLONG_PRECISION);
                }

                // Pics
                if ($result->HotelDescriptiveContents->HotelDescriptiveContent->MultimediaObjects->MultimediaObject) {
                    $pics = [];
                    foreach ($result->HotelDescriptiveContents->HotelDescriptiveContent->MultimediaObjects->MultimediaObject as $object) {
                        if ($object->attributes()->Version == 'Fullsize') {
                            $pics[] = 'https://' . $object->URL;
                        }
                    }
                    if ($pics) {
                        $imported->pics = $pics;
                    } // TO DO: test this.
                }

                // Features
                if ($result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Services) {
                    $featureMap = [
                        1 => null, // 24/7
                        5 => ['extras' => 'ac'], // Air conditioning
                        33 => ['extras' => 'elevator'], // Lift / elevator
                        44 => ['extras' => 'gameroom'], // Games room
                        53 => 'parking', // Indoor parking / garage
                        58 => ['extras' => 'laundry'],
                        71 => ['extras' => 'swimming'], // Swimming pool
                        76 => ['extras' => 'food'], // Restaurant
                        77 => null, // Room service
                        78 => 'lockersInCommons', // Security boxes / Lockers
                        101 => 'wheelchair', // wheelchair access
                        158 => ['extras' => 'bar'], // Bar
                        184 => 'parking', // Parking lot
                        202 => 'bikeRental',
                        218 => ['goodFor' => 'families'], // "children accepted"
                        223 => null, // Internet access
                        224 => ['petsAllowed' => 'yes'], // animals/pets accepted
                        239 => null, // Private beach
                        242 => null, // Heating
                        282 => 'airportPickup', // Pickups / Shuttle from airport
                    ];

                    $importedFeatureCodes = [];
                    foreach ($result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Services->Service as $service) {
                        $featureCode = (int) $service->attributes()->Code;
                        $featureName = (string) $service->Description->Text;
                        if (! array_key_exists($featureCode, $featureMap)) {
                            logError("Add missing Hostelsclub feature: $featureCode // $featureName");

                            continue;
                        }
                        $importedFeatureCodes[] = $featureCode;
                    }
                    if ($importedFeatureCodes) {
                        $imported->features = ListingFeatures::mapFromImportedFeatures($importedFeatureCodes, $featureMap);
                    }
                }

                // Misc
                $imported->localCurrency = (string) $result->HotelDescriptiveContents->HotelDescriptiveContent->attributes()['CurrencyCode'];
            }

            // (note: Hostelsclub doesn't supply location info except when booking)

            // Description

            if (isset($result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Descriptions->Description[0])) {
                $description = (string) $result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Descriptions->Description[0]->Text;
                $actualLanguage = self::importedLangCodeToOurCode((string) $result->HotelDescriptiveContents->HotelDescriptiveContent->HotelInfo->Descriptions->Description[0]->attributes()->Language);
            }
            if ($actualLanguage !== '' && $description !== '' && ! isset($attachments['description'][$actualLanguage])) {
                $attachments['description'][$actualLanguage] = $description;
            }

            // Conditions

            if (isset($result->HotelDescriptiveContents->HotelDescriptiveContent->Policies->Policy->PolicyInfo->Description)) {
                $conditions = (string) $result->HotelDescriptiveContents->HotelDescriptiveContent->Policies->Policy->PolicyInfo->Description->Text;
                $actualLanguage = self::importedLangCodeToOurCode((string) $result->HotelDescriptiveContents->HotelDescriptiveContent->Policies->Policy->PolicyInfo->Description->attributes()->Language);
            }
            if ($actualLanguage !== '' && $conditions !== '' && ! isset($attachments['conditions'][$actualLanguage])) {
                $attachments['conditions'][$actualLanguage] = $conditions;
            }
        }

        $imported->save(); // save any of the various changes that may have been made
        $imported->updateAttachedTexts($attachments);
    }

    public static function importedLangCodeToOurCode($lang): string
    {
        return HostelsclubService::$LANGUAGE_MAP[$lang];
    }
}
