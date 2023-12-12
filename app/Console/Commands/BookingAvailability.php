<?php

/*
Usage:

    php artisan hostelz:bookingAvailability
*/

namespace App\Console\Commands;

use App\Models\Imported;
use App\Services\ImportSystems\ImportSystems;
use Exception;
use Illuminate\Console\Command;

class BookingAvailability extends Command
{
    protected $signature = 'hostelz:bookingAvailability
	    {systemName} {importedIDs} {searchCriteria} {requireRoomDetails} {bookingLinkLocation?}
	    {--asynchronousResponseToken= : Token used when calling AsynchronousExecution to publish the results.}';

    protected $description = 'Called internally to allow asynchronous availability checking of multiple booking systems at once.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $importeds = Imported::whereIn('id', explode(',', $this->argument('importedIDs')))->get();

        $searchCriteria = @unserialize($this->argument('searchCriteria'));
        if (! $searchCriteria) {
            $this->returnResult([]);
            logError("Couldn't unserialize '" . $this->argument('searchCriteria') . "'.");

            return;
        }

        $requireRoomDetails = $this->argument('requireRoomDetails');
        $bookingLinkLocation = $this->argument('bookingLinkLocation') ?? '';
        $systemClassName = ImportSystems::findByName($this->argument('systemName'))->getSystemService();

        try {
            $result = $systemClassName::getAvailability($importeds, $searchCriteria, $requireRoomDetails, $bookingLinkLocation);
        } catch (Exception $e) {
            // If there was an error, before throwing it first return a null result so the caller isn't hung waiting for us.
            $this->returnResult([]); // ( [ ] means no result, but still cached)

            throw $e;
        }

        $this->returnResult($result);
    }

    private function returnResult($result): void
    {
        $this->line(base64_encode(serialize($result)));
    }
}
