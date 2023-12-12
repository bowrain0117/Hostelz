<?php

namespace App\Observers;

use App\Models\HostelsChain;
use Lib\WebsiteTools;

class HostelsChainObserver
{
    /**
     * Handle the HostelsChain "created" event.
     *
     * @param  HostelsChain $hostelsChain
     * @return void
     */
    public function created(HostelsChain $hostelsChain)
    {
        if (! empty($hostelsChain->videoURL)) {
            $this->fillVideoFields($hostelsChain);
        }
    }

    /**
     * Handle the HostelsChain "updated" event.
     *
     * @param  HostelsChain $hostelsChain
     * @return void
     */
    public function updated(HostelsChain $hostelsChain)
    {
        if ($hostelsChain->isDirty('videoURL') && ! empty($hostelsChain->videoURL)) {
            $this->fillVideoFields($hostelsChain);
        }
    }

    private function fillVideoFields(HostelsChain $hostelsChain)
    {
        $videoId = getYoutubeIDFromURL($hostelsChain->videoURL);

        $hostelsChain->videoSchema = WebsiteTools::getVideoSchema($videoId);
        $hostelsChain->videoEmbedHTML = WebsiteTools::extractEmbedCode($hostelsChain->videoURL);

        $hostelsChain->saveQuietly();
    }
}
