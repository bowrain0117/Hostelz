<?php

namespace App\Schemas;

use App\Models\HostelsChain;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\Schema;

class HostelChainSchema
{
    public function __construct(private HostelsChain $hostelChain)
    {
    }

    public function getSchema(): Organization
    {
        $pic = $this->hostelChain->pic;
        $hostelChainLinks = $this->getLinks($this->hostelChain);

        return Schema::organization()
            ->name($this->hostelChain->name)
            ->if(
                $description = $this->hostelChain->description,
                fn (Organization $schema) => $schema->description(clearTextForSchema($description))
            )
            ->if(
                $pic && $pic->url('thumbnails'),
                fn (Organization $schema) => $schema->logo($pic->url('thumbnails'))
            )
            ->if(
                $website = $this->hostelChain->website_link,
                fn (Organization $schema) => $schema->url($website)
            )
            ->if(
                ! empty($hostelChainLinks),
                fn (Organization $schema) => $schema->sameAs($hostelChainLinks)
            );
    }

    private function getLinks(HostelsChain $hostelsChain): array
    {
        $links = [];

        if ($hostelsChain->instagram_link) {
            $links[] = $hostelsChain->instagram_link;
        }

        if ($hostelsChain->videoURL) {
            $links[] = $hostelsChain->videoURL;
        }

        return $links;
    }
}
