<?php

namespace App\Enums;

use App\Enums\Traits\Values;

enum StatusSlp: string
{
    use Values;

    case Publish = 'publish';
    case Draft = 'draft';
}
