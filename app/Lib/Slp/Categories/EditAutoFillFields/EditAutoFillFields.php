<?php

namespace App\Lib\Slp\Categories\EditAutoFillFields;

use App\Enums\CategorySlp;
use App\Models\CityInfo;

abstract class EditAutoFillFields
{
    public function __construct(
        protected int $number,
        protected CityInfo $city,
        protected CategorySlp $category,
    ) {
    }

    public static function create($number, CityInfo $city, CategorySlp $category): self
    {
        return match ($category) {
            CategorySlp::Best => new AutoFillForBestHostels($number, $city, $category),
            CategorySlp::Private => new AutoFillForPrivateHostels($number, $city, $category),
            CategorySlp::Cheap => new AutoFillForCheapHostels($number, $city, $category),
            CategorySlp::Party => new AutoFillForPartyHostels($number, $city, $category),
        };
    }

    abstract public function getTitle();

    abstract public function getMetaTatile();

    abstract public function getMetaDescription();

    abstract public function getContent();
}
