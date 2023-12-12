<?php

namespace App\View\Components\Listing;

use App\Models\Listing\Listing;
use App\Models\Pic;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PicsTopListing extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public Listing $listing,
        public string $picGroup = 'listingPicsTop',
    ) {
    }

    public function render()
    {
        $listingPicsList = $this->getPicsList();

        return view('listing.components.pics-top-listing', compact('listingPicsList'));
    }

    private function getPicsList(): Collection
    {
        return collect($this->listing->getBestPics())
            ->map(
                fn (Pic $pic) => [
                    'srcs' => $this->getSrcs($pic),
                    'title' => $this->getTitle($pic),
                ]
            );
    }

    private function getTitle(Pic $pic): string
    {
        if ($pic->caption) {
            return $pic->caption;
        }

        if ($this->listing?->cityInfo) {
            return "{$this->listing->name}, {$this->listing->cityInfo->city}";
        }

        return $this->listing->name;
    }

    private function getSrcs(Pic $pic): array
    {
        return [
            'jpg' => $pic->url(['thumbnails', 'big']),
            'webp' => $pic->url(['webp_thumbnails', 'big']),
            'tiny' => $pic->url(['tiny', 'big']),
            'big' => $pic->url(['webp_big', 'big']),
        ];
    }
}
