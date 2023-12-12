<?php

namespace App\Lib\AttachedText\Shotrcodes;

use App\Models\AttachedText;
use App\Models\Listing\Listing;
use Closure;

class OtaMainLink
{
    public function handle(AttachedText $item, Closure $next)
    {
        if ($item->subjectType !== 'hostels') {
            return $next($item);
        }

        $listing = Listing::find($item->subjectID);
        if (! $listing) {
            return $next($item);
        }

        $links = $listing->getOtaLinks('att_text');
        if ($links->isEmpty()) {
            return $next($item);
        }

        $link = $links->first()->link;

        $a = "<a href='{$link}' target='_blank' rel='nofollow' title='" . htmlentities($listing->name) . "'>" . htmlentities($listing->name) . '</a>';

        $item->data = str_replace('[otaMainLink]', $a, $item->data);

        return $next($item);
    }
}
