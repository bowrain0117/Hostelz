<?php

namespace App\Listeners;

use App\Events\Import\BatchItemImported;
use App\Events\Import\ImportFinished;
use App\Events\Import\ImportStarted;
use App\Events\Import\ImportUpdate;
use App\Events\Import\InsertImported;
use App\Lib\Import\ImportPage\Listeners\ImportPageListener;

class ImportSubscriber
{
    public function importStarted(ImportStarted $event)
    {
        ImportUpdate::dispatch();

        ImportPageListener::createFor($event->system)
                          ->importStarted($event->options);
    }

    public function importInserted(InsertImported $event)
    {
        ImportUpdate::dispatch();

        ImportPageListener::createFor($event->system)
                          ->importInserted($event->isInserted);
    }

    public function importPageAdded(BatchItemImported $event)
    {
        logNotice('importPageAdded ' . json_encode($event->system));

        ImportUpdate::dispatch();

        ImportPageListener::createFor($event->system)
                          ->importPageAdded($event->page);
    }

    public function importFinished(ImportFinished $event)
    {
        logNotice('importFinished ' . json_encode($event->system));

        ImportUpdate::dispatch();

        ImportPageListener::createFor($event->system)
                          ->importFinished($event->options);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        return [
            ImportStarted::class => 'importStarted',
            InsertImported::class => 'importInserted',
            BatchItemImported::class => 'importPageAdded',
            ImportFinished::class => 'importFinished',
        ];
    }
}
