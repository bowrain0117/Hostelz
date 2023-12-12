<?php

namespace App\Lib\Slp\Categories\EditAutoFillFields;

class AutoFillForBestHostels extends EditAutoFillFields
{
    public function getTitle()
    {
        return sprintf(
            '[number] Best Hostels in %s',
            $this->city->city,
        );
    }

    public function getMetaTatile()
    {
        return sprintf(
            '[number] BEST Hostels in %s (for Solo-Travel in [year])',
            $this->city->city,
        );
    }

    public function getMetaDescription()
    {
        return sprintf(
            'List of TOP Hostels in %s. BEST for Female Solo-Traveler and Backpacker. With Hostel Events and Social Activities (BONUS: Price Comparison)',
            $this->city->city,
        );
    }

    public function getContent()
    {
        return '
            <p>[slp:ListHostels]</p>
            <p>[slp:SliderHostels]</p>
            <p>[slp:AveragePriceGraph]</p>
            <h2>Full List of best Hostels in [city]</h2>
            <p>[slp:CardHostels]</p>
            <p>[slp:TopHostel]</p>
            <p>[slp:FAQ]</p>
        ';
    }
}
