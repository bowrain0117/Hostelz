<?php

namespace App\Lib\Slp\Categories\EditAutoFillFields;

class AutoFillForPartyHostels extends EditAutoFillFields
{
    public function getTitle()
    {
        return sprintf(
            '[number] Party Hostels in %s',
            $this->city->city,
        );
    }

    public function getMetaTatile()
    {
        return sprintf(
            '[number] Best PARTY Hostels in %s in [year] (with Map)',
            $this->city->city,
        );
    }

    public function getMetaDescription()
    {
        return sprintf(
            'List of [number] BEST Party Hostels in %s for Solo-Traveler, Friends, Groups. With Pub Crawls & Hostel Parties. BONUS: Price Comparison',
            $this->city->city,
        );
    }

    public function getContent()
    {
        return '
            <p>[slp:ListHostels]</p>
            <p>[slp:SliderHostels]</p>
            <h2>Full List of Party Hostels in [city]</h2>
            <p>[slp:CardHostels]</p>
            <p>[slp:TopHostel]</p>
            <p>[slp:AveragePriceGraph]</p>
            <p>[slp:FAQ]</p>
        ';
    }
}
