<?php

namespace App\Models\Listing;

use Lib\BaseModel;

class ListingSubscription extends BaseModel
{
    protected $table = 'listing_subscriptions';

    /* Static */

    public static function fieldInfo($purpose = null): void
    {
    }

    public static function maintenanceTasks($timePeriod): void
    {
    }

    /* Accessors & Mutators */

    /* Static */

    /* Scopes */

    /* Relationships */
}
