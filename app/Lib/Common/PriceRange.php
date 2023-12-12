<?php

namespace App\Lib\Common;

readonly class PriceRange
{
    public Price $min;

    public Price $max;

    public Price $avg;

    public function __construct($min, $max, $currency = null)
    {
        $this->min = Price::create($min, $currency);
        $this->max = Price::create($max, $currency);

        $this->avg = $this->getAvg($min, $max, $currency);
    }

    public static function create($min, $max, $currency = null): self
    {
        return new static($min, $max, $currency);
    }

    public static function createFromArray(array $data, $currency = null): self
    {
        return new static($data['min'], $data['max'], $currency);
    }

    public static function createFromObject(object|null $data, $currency = null): self
    {
        return new static($data?->min, $data?->max, $currency);
    }

    public function toArray(): array
    {
        return [
            'min' => $this->min->value,
            'max' => $this->max->value,
            'avg' => $this->avg->value,
        ];
    }

    private function getAvg($min, $max, $currency): Price
    {
        $min = (int) $min;
        $max = (int) $max;

        return Price::create(($min + $max) / 2, $currency);
    }

    public function isset(): bool
    {
        return $this->min->isset();
    }
}
