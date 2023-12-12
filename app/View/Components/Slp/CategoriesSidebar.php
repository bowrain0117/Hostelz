<?php

namespace App\View\Components\Slp;

use App\Models\CityInfo;
use App\Models\SpecialLandingPage;
use App\Services\ListingCategoryPageService;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class CategoriesSidebar extends Component
{
    public function __construct(
        public CityInfo $cityInfo,
        public Collection $cityCategories,
        protected ListingCategoryPageService $categoryService
    ) {
    }

    public function render()
    {
        return view('slp.components.categories-sidebar');
    }

    public function slps(): Collection
    {
        return $this->cityInfo
            ->slp()
            ->published()
            ->get()
            ->map(fn (SpecialLandingPage $slp) => (object) [
                'title' => $slp->meta->title,
                'thumbnail' => $slp->mainPic,
                'path' => $slp->path,
            ]);
    }

    public function categoryPages(): Collection
    {
        return $this->cityCategories
            ->map(fn ($item) => (object) [
                'title' => $item->category->fullName() . ' in ' . $item->cityInfo->city,
                'url' => $item->category->url($item->cityInfo),
            ]);
    }
}
