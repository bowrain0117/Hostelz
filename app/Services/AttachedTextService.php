<?php

namespace App\Services;

use App\Models\AttachedText;
use App\Models\Languages;
use App\Models\Pic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttachedTextService
{
    public function getContinentsAttachedText($continentPics): Collection
    {
        $descriptions = collect();
        foreach ($continentPics as $continent => $cityPic) {
            if (! $this->continentDescription($continent)->exists()) {
                $this->createDraftForContinent($continent, $cityPic);

                continue;
            }

            $descriptions[$continent] = $this->continentDescription($continent)->first();
        }

        return $descriptions;
    }

    private function continentDescription(string $continent): Builder
    {
        return AttachedText::continentInfo($continent);
    }

    private function createDraftForContinent(string $continent, Pic $cityPic): void
    {
        $textToAttach = new AttachedText([
            'subjectType' => 'continentInfo',
            'subjectID' => $cityPic->subjectID,
            'subjectString' => $continent,
            'type' => 'description',
            'userID' => auth()->id(),
            'status' => 'draft',
            'language' => Languages::currentCode(),
            'lastUpdate' => date('Y-m-d'),
        ]);

        $textToAttach->save();
    }
}
