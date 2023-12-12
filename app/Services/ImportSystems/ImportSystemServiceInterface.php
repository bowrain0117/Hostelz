<?php

namespace App\Services\ImportSystems;

use App\Booking\SearchCriteria;
use App\Models\Imported;
use Illuminate\Support\Collection;

interface ImportSystemServiceInterface
{
    /*
        Returns true if everything is ok and all previous version imported records for the system should be set to inactive,
        or false if the import failed or if the previously imported records should be left as is.
    */

    public static function import($isTestRun);

    /*
        Makes changes to $imported and calls $imported->save() if changes are made that should be saved.
        Calls $imported->updateAttachedTexts($attachments) to update attached text.
            $attachments[]: description, location, reviews, conditions.
    */
    public static function updateDataForImported(Imported $imported);

    public static function isActive(Imported $imported);

    public static function getDefaultLinkRedirect(string $bookingLinkLocation);

    public static function getStaticLinkRedirect(string $urlLink, string $bookingLinkLocation, string $importedId = null);

    public static function decodeBookingLinkInfo(string $bookingLinkInfo, string $bookingLinkLocation);

    public static function getNextDaysAvailability(Collection $importeds, SearchCriteria $searchCriteria, bool $roomDetails, int $initialNightsValue);
}
