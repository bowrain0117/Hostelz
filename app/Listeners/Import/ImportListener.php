<?php

namespace App\Listeners\Import;

use App\Events\Import\ImportStarted;
use App\Lib\Import\ImportPage\Listeners\ImportPageListener;

class ImportListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Import\ImportStarted  $event
     * @return void
     */
    public function handle(ImportStarted $event)
    {
        logNotice(json_encode($event->system));
        logWarning(json_encode($event->options));

        ImportPageListener::createFor($event->system)
                          ->importStarted($event->options);
    }
}
