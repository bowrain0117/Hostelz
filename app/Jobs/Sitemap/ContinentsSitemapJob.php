<?php

namespace App\Jobs\Sitemap;

use App\Models\ContinentInfo;
use App\Models\CountryInfo;
use Illuminate\Support\Collection;

class ContinentsSitemapJob extends SitemapBaseJob
{
    public const KEY = 'continents';

    protected function getLinks(): Collection
    {
        $langCodes = $this->getLanguages();

        return collect(ContinentInfo::all())
            ->map(
                fn (ContinentInfo $continent) => $langCodes
                    ->map(
                        fn ($lang) => collect([$continent->getURL('absolute', $lang)])
                            ->push(
                                $continent
                                    ->countries()
                                    ->map(
                                        fn (CountryInfo $country) => collect([$country->getURL('absolute', $lang)])
                                            ->push(
                                                $country->regions()->map(
                                                    fn ($region) => $region->getRegionURL('absolute', $lang)
                                                )
                                            )
                                            ->push(
                                                $country->cityGroups()->map(
                                                    fn ($cityGroup) => $cityGroup->getCityGroupURL('absolute', $lang)
                                                )
                                            )
                                    )
                            )
                    )
            )
            ->flatten();
    }
}
