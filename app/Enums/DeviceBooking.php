<?php

namespace App\Enums;

use App\Enums\Traits\Values;

enum DeviceBooking: string
{
    use Values;

    case Mobile = 'mobile';
    case Desktop = 'desktop';
    case Tablet = 'tablet';
    case App = 'app';
}
