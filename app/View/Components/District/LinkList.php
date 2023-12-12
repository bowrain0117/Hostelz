<?php

namespace App\View\Components\District;

use App\Models\CityInfo;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class LinkList extends Component
{
    public Collection $districts;

    public function __construct(
        public CityInfo $city
    ) {
        $this->districts = $this->city->districts()->active()->get();
    }

    public function render(): View|Closure|string
    {
        return view('districts.components.link-list');
    }

    public function shouldRender(): bool
    {
        return $this->districts->isNotEmpty();
    }
}
