<?php

namespace App\Lib\AttachedText\Shotrcodes;

use App\Models\AttachedText;
use App\Models\Listing\Listing;
use Closure;

class HostelNameLink
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

        $a = "<a href='{$listing->path}' target='_blank' title='" . htmlentities($listing->name) . "'>" . htmlentities($listing->name) . '</a>';

        $item->data = str_replace(['[hostelNameLink]', '[hostelNameListingLink]'], $a, $item->data);

        return $next($item);
    }
}
