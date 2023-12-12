<?php

namespace App\View\Components\District;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Neighborhood extends Component
{
    public function __construct(
        public string $cityName,
        public Collection $items,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view(
            'districts.components.neighborhood',
        );
    }

    public function shouldRender()
    {
        return filled($this->items);
    }
}
