<?php

namespace App\View\Components\City;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HeroTitle extends Component
{
    public function __construct(
        public array $metaValues,
        public string $pageType,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view('city.components.hero-title');
    }

    public function headerTitle(): string
    {
        if (isset($this->metaValues['headerTitle'])) {
            return $this->metaValues['headerTitle'];
        }

        if ($this->pageType === 'district' && isset($this->metaValues['districtTitle'])) {
            return $this->metaValues['districtTitle'];
        }

        if ($this->metaValues['hostelCount'] === 0 && $this->metaValues['count']) {
            return langGet('city.TopTitleNoHostelCheapPlaces', $this->metaValues);
        }

        if ($this->metaValues['hostelCount'] === 1) {
            return langGet('city.TopTitle1Hostel', $this->metaValues);
        }

        /* "backpackers" is more commonly used in Oceania */
        if ($this->metaValues['continent'] === 'Australia & Oceania') {
            return langGet('city.TopTitleHostelsOceania', $this->metaValues);
        }

        return langGet('city.TopTitleHostels', $this->metaValues);
    }

    public function headerText(): string
    {
        if ($this->metaValues['hostelCount'] >= 2) {
            /* "backpackers" is more commonly used in Oceania */
            if ($this->metaValues['continent'] === 'Australia & Oceania') {
                $headertext = langGet('city.TopTextHostelsOceania', $this->metaValues);
            } else {
                $headertext = langGet('city.TopTextHostels', $this->metaValues);
            }
        } elseif ($this->metaValues['hostelCount'] === 1) {
            $headertext = langGet('city.TopText1Hostel', $this->metaValues);
        } elseif ($this->metaValues['hostelCount'] === 0 && $this->metaValues['count']) {
            $headertext = langGet('city.TopTextNoHostelCheapPlaces', $this->metaValues);
        } else {
            $headertext = langGet('city.TopTextFallback', $this->metaValues);
        }

        return $headertext;
    }
}
