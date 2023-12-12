<?php

namespace app\Enums\Traits;

use BackedEnum;
use Illuminate\Support\Collection;

trait Values
{
    /** Get an array of case values. */
    public static function values(): Collection
    {
        $cases = static::cases();

        $key = isset($cases[0]) && $cases[0] instanceof BackedEnum
            ? 'value'
            : 'name';

        return collect(array_column($cases, $key));
    }
}
