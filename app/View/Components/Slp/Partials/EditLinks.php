<?php

namespace App\View\Components\Slp\Partials;

use App\Enums\CategorySlp;
use App\Models\Listing\Listing;
use Illuminate\View\Component;

class EditLinks extends Component
{
    public $links;

    public function __construct(
        public Listing $listing,
        public CategorySlp $category
    ) {
        $this->links = $this->getLinks($this->listing);
    }

    public function render()
    {
        return view('slp.components.edit-links');
    }

    private function getLinks(Listing $listing): ?array
    {
        if (! auth()?->user()?->isAdmin()) {
            return null;
        }

        return [
            'text' => routeURL(
                'staff-listingSpecialText',
                'edit-or-create'
            ) . "?listingID={$listing->id}&type={$this->category->value}",
            'booking' => $listing->getBdcImporteds()->first()?->urlLink,
            'listing' => routeURL('staff-listings', $listing->id),
            'map' => $listing->geoPoint()?->mapLink(),
        ];
    }

    public function shouldRender(): bool
    {
        return $this->links !== null;
    }
}
