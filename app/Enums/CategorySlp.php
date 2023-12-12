<?php

namespace App\Enums;

use App\Enums\Traits\Values;

enum CategorySlp: string
{
    use Values;

    case Best = 'best-hostels';
    case Cheap = 'cheap-hostels';
    case Party = 'party-hostels';
    case Private = 'private-hostels';

    public function title(): string
    {
        return match ($this) {
            self::Best => 'best hostels',
            self::Party => 'party hostels',
            self::Cheap => 'cheap hostels',
            self::Private => 'private hostels',
        };
    }
}
