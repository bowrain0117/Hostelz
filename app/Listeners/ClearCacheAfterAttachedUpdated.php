<?php

namespace App\Listeners;

use App\Events\AttachedTextUpdated;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;

class ClearCacheAfterAttachedUpdated
{
    public function handle(AttachedTextUpdated $event)
    {
        cache()->tags(["attached:$event->attachedText->id"])->flush();

        if ($event->attachedText->subjectType === 'hostels') {
            cache()->tags(["listing:$event->attachedText->subjectID"])->flush();

            $listing = Listing::find($event->attachedText->subjectID);

            SpecialLandingPage::query()
                ->forCity($listing->city)
                ->get()
                ->filter->hasListing($listing->id)
                ->each(fn (SpecialLandingPage $slp) => cache()->tags(["slp:$slp->id"])->flush());
        }
    }
}
