<?php

namespace App\Lib\Common;

use Lib\Currencies;

readonly class Price
{
    public string $value;

    public string $currency;

    public string $formated;
//    public Price $origin;

    public function __construct(
        string|null $value,
        string|null $currency = null
    ) {
        $this->value = $value ?? '';
        $this->currency = $currency ?? Currencies::defaultCurrency();

//        $this->origin = self::create($this->value, $this->currency);

        $this->formated = $this->value !== '' ?
            Currencies::convert(
                amount: $this->value,
                fromCurrency:  Currencies::defaultCurrency(),
                toCurrency: $this->currency,
                formatted: true) :
            '';
    }

    public static function create(string|null $value, string|null $currency = null): self
    {
        return new static($value, $currency);
    }

    public function isset(): bool
    {
        return $this->value !== '';
    }

    public function isLower(self|null $other): bool
    {
        if (is_null($other)) {
            return true;
        }

        return (float) $this->value < (float) $other->value;
    }

    public function __toString()
    {
        return $this->formated;
    }
}
