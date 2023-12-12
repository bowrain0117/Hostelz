<?php

namespace App\Lib\Listings;

use App\Booking\RoomInfo;
use App\Lib\Common\Price;
use App\Lib\Common\PriceRange;
use Illuminate\Support\Collection;

readonly class ListingPrices
{
    public Price|null $min;

    public Price|null $max;

    public PriceRange|null $dorm;

    public PriceRange|null $private;

    public PriceRange|null $range;

    public function __construct(Collection|array $data, $currency = null)
    {
        $data = is_a($data, Collection::class) ? $data : collect($data);
        if ($data->isEmpty()) {
            $this->min = null;
            $this->max = null;
            $this->dorm = null;
            $this->private = null;
            $this->range = null;

            return;
        }

        $this->dorm = PriceRange::createFromObject($data->get(RoomInfo::TYPE_DORM), $currency);
        $this->private = PriceRange::createFromObject($data->get(RoomInfo::TYPE_PRIVATE), $currency);

        $prices = collect([...array_values($this->dorm->toArray()), ...array_values($this->private->toArray())])->filter();

        // todo: maybe later delete custom.minPriceCoefficient !!!
        $this->min = Price::create($prices->min() * config('custom.minPriceCoefficient'), $currency);
        $this->max = Price::create($prices->max(), $currency);

        $this->range = PriceRange::create($this->min->value, $this->max->value, $currency);
    }

    public static function create($data, $currency = null): self
    {
        return new static($data, $currency);
    }
}
