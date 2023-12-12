<?php

namespace App\Enums\Listing;

use app\Enums\Traits\Values;
use App\Models\CityInfo;

enum CategoryPage: string
{
    use Values;

    case Family = 'family-hostels';
    case Youth = 'youth-hostels';

    public const TABLE_KEY = 'categoryPage';

    public const MIN_LISTINGS_COUNT = 2;

    public function suitableFor(): ?string
    {
        return match ($this) {
            self::Family => 'families',
            self::Youth => 'youth_hostels',
        };
    }

    public function fullName(): string
    {
        return match ($this) {
            self::Family => 'Family Hostels',
            self::Youth => 'Youth Hostels',
        };
    }

    public function title($cityName)
    {
        return langGet(
            'SeoInfo.Category' . $this->name . 'Title',
            [
                'city' => $cityName,
                'year' => date('Y'),
            ]
        );
    }

    public function metaTitle($cityName)
    {
        return langGet(
            'SeoInfo.Category' . $this->name . 'MetaTitle',
            [
                'city' => $cityName,
                'year' => date('Y'),
            ]
        );
    }

    public function metaDescription($cityName)
    {
        return langGet(
            'SeoInfo.Category' . $this->name . 'MetaDescription',
            [
                'city' => $cityName,
                'year' => date('Y'),
            ]
        );
    }

    public function attachmentType(): string
    {
        return self::TABLE_KEY . ':' . $this->value;
    }

    public function keyValue(): string
    {
        return self::TABLE_KEY . '_' . $this->value;
    }

    public function url(CityInfo $cityUrl): string
    {
        return $cityUrl->getUrlWithoutRegion() . '/' . $this->value;
    }

    public function editUrl(CityInfo $cityInfo)
    {
        return routeURL(
            'staff-cityCategoryPageDescription',
            ['edit-or-create', 'id' => $cityInfo->id, 'type' => $this->attachmentType()]
        );
    }

    public static function tryFromTableKey(string $key): ?self
    {
        return self::tryFrom(str($key)->after(':'));
    }
}
