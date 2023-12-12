<?php

namespace App\Lib\Slp\Categories\EditAutoFillFields;

class AutoFillForCheapHostels extends EditAutoFillFields
{
    public function getTitle()
    {
        return sprintf(
            '[number] Cheapest Hostels in %s',
            $this->city->city,
        );
    }

    public function getMetaTatile()
    {
        return sprintf(
            '[number] CHEAPEST Hostels in %s (Sorted by Price in [year])',
            $this->city->city,
        );
    }

    public function getMetaDescription()
    {
        return sprintf(
            'List of [number] CHEAPEST %s Hostels in [year]. Sorted by Price per night. For Solo-Traveler, Students, Backpacker. (EXTRA: Price Comparison)',
            $this->city->city,
        );
    }

    public function getContent()
    {
        return '
            <p>[slp:CheapHostelsList]</p>
            <p>[slp:SliderHostels]</p>
            <h2>Full List of Affordable Hostels in [city]</h2>
            <p>[slp:CheapHostelsCard]</p>
            <p>[slp:AveragePriceGraph]</p>
            <p>[slp:WhenBook]</p>
        ';
    }
}
