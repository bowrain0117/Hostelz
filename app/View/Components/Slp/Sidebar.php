<?php

namespace App\View\Components\slp;

use App\Enums\CategorySlp;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use App\Services\ImportSystems\BookHostels\BookHostelsService;
use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public function __construct(
        public SpecialLandingPage $slp
    ) {
    }

    public function render()
    {
        $topHostel = $this->slp->hostels->first();

        return view(
            'slp.components.sidebar',
            [
                'authorName' => $this->slp->author->nickname,

                'date' => $this->slp->updated_at->format('F Y'),

                'city' => $this->slp->subjectable->city,

                'topHostel' => [
                    'url' => $this->getUrl($topHostel),
                    'name' => $topHostel?->name,
                    'pic' => $topHostel?->thumbnailURL(),
                    'title' => $this->getTopHostelTitle(),
                ],

                'HWLink' => $this->getHWLink(),
            ]
        );
    }

    private function getUrl(Listing|null $listing): string|null
    {
        if ($listing === null) {
            return null;
        }

        $import = $listing->getHwImporteds()->first();

        return $import
            ? BookHostelsService::getStaticLinkRedirect(
                $import->urlLink,
                $this->getLinkLocation()
            )
            : BookHostelsService::getDefaultLinkRedirect(
                $this->getLinkLocation()
            );
    }

    private function getHWLink(): string
    {
        return BookHostelsService::getDefaultLinkRedirect(
            $this->getLinkLocation()
        );
    }

    private function getLinkLocation(): string
    {
        return getCMPLabel('slp_sidebar', $this->slp->subjectable->city);
    }

    private function getTopHostelTitle()
    {
        return match ($this->slp->category) {
            CategorySlp::Party => sprintf('#1 Party Hostel in %s', $this->slp->subjectable->city),
            CategorySlp::Cheap => sprintf('Cheapest Hostel in %s', $this->slp->subjectable->city),
            CategorySlp::Private => sprintf('Best Hostel in %s with Private Rooms:', $this->slp->subjectable->city),
            default => sprintf('Best Hostel in %s is...', $this->slp->subjectable->city),
        };
    }
}
