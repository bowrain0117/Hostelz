<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Faq extends Component
{
    public Collection $faqs;

    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
        $this->faqs = $this->slp->faqs;
    }

    public function render()
    {
        return view(
            'slp.components.shortcodes.faq',
            [
                'cityInfo' => $this->slp->subjectable,
                'priceAVG' => $this->slp->subjectable->getPriceAVG(),
            ]
        );
    }
}
