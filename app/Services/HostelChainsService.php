<?php

namespace App\Services;

use App\Models\HostelsChain;

class HostelChainsService
{
    public const TOP_CITIES_MAX_NUMBER = 5;

    public function getFAQs(HostelsChain $hostelChain)
    {
        $citiesInChain = $this->getAllCitiesInChain($hostelChain)->unique()->count();
        $topCitiesInChain = $this->getTopCitiesInChain($hostelChain)->implode('city', ', ');
        $hostelsCount = $hostelChain->listingsCount;

        return [
            [
                'question' => __('HostelsChain.faqs.Question1', ['name' => $hostelChain->name]),
                'answer' => __('HostelsChain.faqs.Answer1', ['hostelsCount' => $hostelsCount, 'citiesCount' => $citiesInChain]),
            ],
            [
                'question' => __('HostelsChain.faqs.Question2', ['name' => $hostelChain->name]),
                'answer' => __('HostelsChain.faqs.Answer2', ['name' => $hostelChain->name, 'hostelsCount' => $hostelsCount]),
            ],
            [
                'question' => __('HostelsChain.faqs.Question3', ['name' => $hostelChain->name]),
                'answer' => __('HostelsChain.faqs.Answer3', ['name' => $hostelChain->name, 'cities' => $topCitiesInChain]),
            ],
        ];
    }

    public function getTopCitiesInChain(HostelsChain $hostelChain)
    {
        return $this->getAllCitiesInChain($hostelChain)
            ->unique()
            ->sortByDesc('hostelCount')
            ->take(self::TOP_CITIES_MAX_NUMBER);
    }

    public function getAllCitiesInChain(HostelsChain $hostelChain)
    {
        return $hostelChain->listings->map(function ($listing) {
            return $listing->cityInfo;
        });
    }
}
