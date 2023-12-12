<?php

namespace App\View\Components;

use App\Schemas\FaqsSchema;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Faqs extends Component
{
    public function __construct(
        public Collection $faqs,
        public string $city,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view('components.faqs');
    }

    public function schema()
    {
        return FaqsSchema::for($this->faqs)->getSchema();
    }

    public function shouldRender()
    {
        return filled($this->faqs);
    }
}
