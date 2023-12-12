<?php

namespace App\View\Components\District;

use App\Models\District;
use App\Services\ListingCategoryPageService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Main extends Component
{
    public function __construct(
        public District $district,
        public ?array $listingsData,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view(
            'districts.components.main',
            [
                'cityInfo' => $this->district->city ?? null,
                'faqs' => $this->district->faqs,
                'listingsData' => $this->listingsData,
                'cityCategories' => resolve(ListingCategoryPageService::class)
                    ->activeForCity($this->district->city),
            ]
        );
    }
}
