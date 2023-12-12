<?php

namespace App\View\Components\Slp;

use App\Models\SpecialLandingPage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class MoreGuides extends Component
{
    public Collection $items;

    public function __construct(
        public SpecialLandingPage $slp)
    {
        $this->items = $this->items();
    }

    public function render(): View|Closure|string
    {
        return view('slp.components.more-guides');
    }

    private function items()
    {
        return cache()
            ->tags(['slp:' . $this->slp->id])
            ->remember(
                'more-guides:' . $this->slp->id,
                config('custom.page_cache_time'),
                function () {
                    return SpecialLandingPage::query()
                        ->published()
                        ->forCity($this->slp->subjectable->city)
                        ->where('id', '!=', $this->slp->id)
                        ->get()
                        ->map(fn (SpecialLandingPage $slp) => (object) [
                            'slug_id' => $slp->slug_id,
                            'title' => $slp->meta->title,
                            'thumbnail' => $slp->mainPic,
                            'path' => $slp->path,
                        ]);
                });
    }

    public function shouldRender()
    {
        return $this->items->isNotEmpty();
    }
}
