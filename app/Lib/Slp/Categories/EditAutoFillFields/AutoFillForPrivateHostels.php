<?php

namespace App\Lib\Slp\Categories\EditAutoFillFields;

class AutoFillForPrivateHostels extends EditAutoFillFields
{
    public function getTitle()
    {
        return sprintf(
            '[number] Hostels in %s with Private Rooms',
            $this->city->city,
        );
    }

    public function getMetaTatile()
    {
        return sprintf(
            '[number] BEST Hostels with Private Rooms in %s (for Couples)',
            $this->city->city,
        );
    }

    public function getMetaDescription()
    {
        return sprintf(
            'List of [number] TOP Hostels in %s with Private Rooms. Best for Couples and Families. With Double Rooms and Family Rooms (BONUS: Price Comparison)',
            $this->city->city,
        );
    }

    public function getContent()
    {
        return '
            <p>[slp:ListHostels]</p>
            <p>[slp:SliderHostels]</p>
            <h2>Full List of Hostels in [city] with Private Rooms</h2>
            <p>[slp:PrivateHostels]</p>
            <p>[slp:AveragePriceGraph]</p>
            <p>[slp:WhenBook]</p>
        ';
    }
}
