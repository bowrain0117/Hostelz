<?php

namespace App\Services\ImportSystems;

use App\Events\Import\ImportUpdate;
use App\Events\Import\InsertImported;
use App\Models\Imported;
use App\Models\Listing\Listing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lib\GeoPoint;

class Import
{
    public static function fetchSystemsData(string $otaName = '', bool $isTestRun = false): void
    {
        self::configuration();

        cache()->tags('imported')->flush();

        foreach (self::getSystems($otaName) as $systemInfo) {
            set_time_limit(2 * 60 * 60); // (some import systems code may also reset the limit to their own limit)

            $systemInfo->getSystemService()::import($isTestRun);
        }
    }

    public static function insertNewImported(array $newValues, array $attachedTexts = []): bool
    {
        cache()->tags('imported')->increment('checkedImported');

        ImportUpdate::dispatch();

        InsertImported::dispatch($newValues['system']);

        if (! self::isNewImport($newValues)) {
            return false;
        }

        $newValues = self::prepareGeoPoint($newValues);

        self::insertNew($newValues, $attachedTexts);

        return true;
    }

    private static function insertNew(array $newValues, $attachedTexts = []): void
    {
        $newValues['previousName'] = $newValues['name'];

        // Check for at least some valid data of some kind...
        if ($newValues['name'] === '' || $newValues['propertyType'] === '') {
            Log::channel('importedImport')->error('Missing required data.');

            return;
        }

        $new = new Imported();
        // (We set each value explicitly rather than passing the values to the constructor so that they properly use accessors/mutators/etc.)
        foreach ($newValues as $field => $newValue) {
            $new->$field = $newValue;
        }
        $new->save();

        cache()->tags('imported')->increment('insertNewImported');
        InsertImported::dispatch($newValues['system'], true);

        Log::channel('importedImport')->info("Insert new Imported city: '{$new->city}' id: '{$new->id}");

        if ($attachedTexts) {
            $new->updateAttachedTexts($attachedTexts);
        }
    }

    public static function update(Imported $imported, array $values, array $attachedTexts = []): void
    {
//        todo: update() does not work due to StringSetDataType()
//        $imported->update($values);

        // (We set each value explicitly rather than passing the values to the constructor so that they properly use accessors/mutators/etc.)
        foreach ($values as $field => $value) {
            $imported->$field = $value;
        }
        $imported->save();

        $imported->refresh();

        if ($attachedTexts) {
            $imported->updateAttachedTexts($attachedTexts);
        }
    }

    private static function getSystems($systemName): array
    {
        return ($systemName === '') ?
            ImportSystems::allActive() :
            [$systemName => ImportSystems::findByName($systemName)];
    }

    private static function prepareGeoPoint($newValues)
    {
        if (empty($newValues['latitude']) && empty($newValues['longitude'])) {
            return $newValues;
        }
        // round to avoid "data truncated" warnings
        $newValues['latitude'] = round((float) $newValues['latitude'], Listing::LATLONG_PRECISION);
        $newValues['longitude'] = round((float) $newValues['longitude'], Listing::LATLONG_PRECISION);

        if (! with(new GeoPoint($newValues['latitude'], $newValues['longitude']))->isValid()) {
            logWarning("Invalid latitude/longitude ($newValues[latitude], $newValues[longitude]).");
            unset($newValues['latitude'], $newValues['longitude']);
        }

        return $newValues;
    }

    public static function getInitialValues(string $systemName): array
    {
        $version = cache()->tags('imported')->remember(
            'importSystemVersion:' . $systemName,
            now()->addDay(),
            fn () => Imported::where('system', $systemName)->max('version')
        );

        // Initialize $importedData with some values
        return [
            'status' => 'active',
            'version' => $version ?? 1,
            'system' => $systemName,
            'availability' => ImportSystems::findByName($systemName)->onlineBooking,
        ];
    }

    private static function configuration(): void
    {
        // we were having issues with the script not completing with less memory
        if (ini_get('memory_limit') < 512) {
            ini_set('memory_limit', '512M');
        }
        // to save memory
        DB::disableQueryLog();
    }

    private static function isNewImport($newValues): bool
    {
        // * Find any existing matching Imported record */
        if (! $newValues['intCode'] && $newValues['charCode'] === '') {
            Log::channel('importedImport')->warning('Missing intCode/charCode. for ' . json_encode($newValues));

            return false;
        }

        $intCode = isset($newValues['intCode']) ? (int) $newValues['intCode'] : 0;
        $charCode = isset($newValues['charCode']) ? (string) $newValues['charCode'] : '';

        return ! Imported::where([
            ['intCode', $intCode],
            ['charCode', $charCode],
            ['system', $newValues['system']],
        ])
            ->exists();
    }
}
