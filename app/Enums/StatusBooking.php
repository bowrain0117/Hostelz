<?php

namespace App\Enums;

use App\Enums\Traits\Values;

enum StatusBooking: string
{
    use Values;

    case Booked = 'booked';
    case Stayed = 'stayed';
    case Cancelled = 'cancelled';
}
