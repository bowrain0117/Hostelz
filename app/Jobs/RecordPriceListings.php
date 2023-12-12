<?php

namespace App\Jobs;

use App\Models\Listing\Listing;
use App\Models\PriceHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordPriceListings implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $listingIds;

    public $availabilityByListingID;

    public $searchCriteria;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($listingIds, $availabilityByListingID, $searchCriteria)
    {
        $this->listingIds = $listingIds;
        $this->availabilityByListingID = $availabilityByListingID;
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->listingIds as $listingID) {
            if (empty($this->availabilityByListingID[$listingID])) {
                continue;
            }

            PriceHistory::recordPrice(Listing::find($listingID), $this->searchCriteria, $this->availabilityByListingID[$listingID]);
        }
    }
}
