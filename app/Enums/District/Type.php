<?php

namespace App\Enums\District;

use Illuminate\Support\Collection;

enum Type:string
{
    case In = 'in';
    case Near = 'near';

    public static function getValues(): Collection
    {
        return collect(self::cases())->map(fn ($item) => $item->value);
    }
}
