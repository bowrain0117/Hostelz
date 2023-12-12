<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Listing\Listing;

class CheckAvailabilityListingStaffController extends Controller
{
    public function __invoke(Listing $listing)
    {
        $message = '';

        return view(
            'staff/listings/checkAvailability',
            compact('message', 'listing')
        );
    }
}
