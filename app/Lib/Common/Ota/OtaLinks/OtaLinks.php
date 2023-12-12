<?php

namespace App\Lib\Common\Ota\OtaLinks;

use App\Models\Listing\Listing;
use Illuminate\Support\Collection;

readonly class OtaLinks
{
    public ?OtaLink $main;

    public Collection $all;

    public function __construct(public Listing $listing, $cmpLabel)
    {
        $this->all = $this->listing->getOtaLinks($cmpLabel);
        $this->main = $this->getMain();
    }

    public static function create(Listing $listing, $cmpLabel): self
    {
        return new static($listing, $cmpLabel);
    }

    private function getMain()
    {
        return $this->all?->first();
    }
}
