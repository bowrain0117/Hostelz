<?php

namespace App\View\Components\City;

use App\Models\CityInfo;
use App\Schemas\FaqsSchema;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Faq extends Component
{
    public Collection $faqs;

    public function __construct(
        public CityInfo $cityInfo,
        protected       $priceAVG
    ) {
        $this->faqs = $this->getFaqs();
    }

    public function render()
    {
        return view('city.components.faq');
    }

    public function schema()
    {
        return FaqsSchema::for($this->faqs)->getSchema();
    }

    protected function getFaqs()
    {
        $couples = $this->cityInfo->getBestHostelByType('couples');
        $families = $this->cityInfo->getBestHostelByType('families');
        $groups = $this->cityInfo->getBestHostelByType('groups');

        return collect([
            [
                'question' => langGet('city.FAQQuestion1', ['city' => $this->cityInfo->translation()->city]),
                'answer' => $this->getAnswer1($this->cityInfo, $this->priceAVG),
            ],
            [
                'question' => langGet('city.FAQQuestion2', ['city' => $this->cityInfo->translation()->city]),
                'answer' => $this->getAnswer2($this->cityInfo),
            ],
            [
                'question' => langGet('city.FAQQuestion3', ['city' => $this->cityInfo->translation()->city]),
                'answer' => ($couples) ? langGet(
                    'city.FAQAnswer3',
                    [
                        'city' => $this->cityInfo->translation()->city,
                        'HostelNameCouples' => $couples->name,
                        'HostelNameCouplesLink' => $couples->getURL(),
                    ]
                ) : '',
            ],
            [
                'question' => langGet('city.FAQQuestion4', ['city' => $this->cityInfo->translation()->city]),
                'answer' => ($families) ? langGet(
                    'city.FAQAnswer4',
                    [
                        'city' => $this->cityInfo->translation()->city,
                        'HostelNameFamilies' => $families->name,
                        'HostelNameFamiliesLink' => $families->getURL(),
                    ]
                ) : '',
            ],
            [
                'question' => langGet('city.FAQQuestion5', ['city' => $this->cityInfo->translation()->city]),
                'answer' => ($groups) ? langGet(
                    'city.FAQAnswer5',
                    [
                        'city' => $this->cityInfo->translation()->city,
                        'HostelNameGroups' => $groups->name,
                        'HostelNameGroupsLink' => $groups->getURL(),
                    ]
                ) : '',
            ],
            [
                'question' => langGet('city.FAQQuestion6', ['city' => $this->cityInfo->translation()->city]),
                'answer' => (0) ? langGet('city.FAQAnswer6') : '',
            ],
            [
                'question' => langGet('city.FAQQuestion7', ['city' => $this->cityInfo->translation()->city]),
                'answer' => $this->getAnswer7($this->cityInfo),
            ],
            [
                'question' => langGet('city.FAQQuestion8', ['city' => $this->cityInfo->translation()->city]),
                'answer' => $this->getAnswer8($this->cityInfo),
            ],
            [
                'question' => langGet('city.FAQQuestion9', ['city' => $this->cityInfo->translation()->city]),
                'answer' => $this->getAnswer9($this->cityInfo),
            ],
        ])->filter(fn ($item) => filled($item['answer']))->values();
    }

    protected function getAnswer1($cityInfo, $priceAVG)
    {
        if (
            ! $priceAVG ||
            ((int) $priceAVG['dorm'] === 0 || (int) $priceAVG['private'] === 0)
        ) {
            return '';
        }

        return langGet(
            'city.FAQAnswer1',
            [
                'city' => $cityInfo->translation()->city,
                'AverageDormPrice' => $priceAVG['dorm'],
                'AveragePrivatePrice' => $priceAVG['private'],
            ]
        );
    }

    protected function getAnswer2($cityInfo)
    {
        if (! $cityInfo->hostelCount) {
            return '';
        }

        if ($cityInfo->hostelCount === 1) {
            return langGet(
                'city.FAQAnswer2a',
                [
                    'city' => $cityInfo->translation()->city,
                    'country' => $cityInfo->translation()->country,
                    'HostelCount' => $cityInfo->hostelCount,
                ]
            );
        }

        return langGet(
            'city.FAQAnswer2',
            ['city' => $cityInfo->translation()->city, 'HostelCount' => $cityInfo->hostelCount]
        );
    }

    protected function getAnswer7($cityInfo)
    {
        $best2FST = $cityInfo->getBestTwoFemaleSoloTraveller();
        if (! $best2FST) {
            return '';
        }

        if (count($best2FST) === 1) {
            return langGet(
                'city.FAQAnswer7a',
                [
                    'city' => $cityInfo->translation()->city,
                    'HostelNameFemaleSolo1Link' => $best2FST[0]->getURL(),
                    'HostelNameFemaleSolo1' => $best2FST[0]->name,
                ]
            );
        }

        if (count($best2FST) === 2) {
            return langGet(
                'city.FAQAnswer7b',
                [
                    'city' => $cityInfo->translation()->city,
                    'HostelNameFemaleSolo1Link' => $best2FST[0]->getURL(),
                    'HostelNameFemaleSolo1' => $best2FST[0]->name,
                    'HostelNameFemaleSolo2Link' => $best2FST[1]->getURL(),
                    'HostelNameFemaleSolo2' => $best2FST[1]->name,
                ]
            );
        }

        return '';
    }

    protected function getAnswer8($cityInfo)
    {
        $propertyTypeCounts = $cityInfo->getListingCounts();
        if (
            ! $propertyTypeCounts ||
            (isset($propertyTypeCounts['hostel']) && $propertyTypeCounts['hostel'] < 1)
        ) {
            return '';
        }

        $answer = '';

        if (! empty($propertyTypeCounts['hostel'])) {
            $answer .= trans_choice(
                'city.FAQAnswer8a',
                $cityInfo->hostelCount,
                [
                    'city' => $cityInfo->translation()->city,
                    'country' => $cityInfo->translation()->country,
                    'HostelCount' => $cityInfo->hostelCount,
                ]
            );
        } else {
            $answer .= langGet('city.FAQAnswer8b', ['city' => $cityInfo->translation()->city]);
        }

        $hasOtherPropertyType = ! empty($propertyTypeCounts['hotel']) || ! empty($propertyTypeCounts['guesthouse']) || ! empty($propertyTypeCounts['apartment']);

        if ($hasOtherPropertyType) {
            $answer .= ' ' . langGet('city.FAQAnswer8c', ['city' => $cityInfo->translation()->city]);
            $answer .= "<ul class='my-3'>";
        }

        if (! empty($propertyTypeCounts['hotel'])) {
            $answer .= '<li>' . trans_choice(
                'city.FAQAnswer8Hotel',
                $propertyTypeCounts['hotel'],
                ['hotels' => $propertyTypeCounts['hotel']]
            ) . '</li>';
        }

        if (! empty($propertyTypeCounts['guesthouse'])) {
            $answer .= '<li>' . trans_choice(
                'city.FAQAnswer8Guesthouses',
                $propertyTypeCounts['guesthouse'],
                ['guesthouse' => $propertyTypeCounts['guesthouse']]
            ) . '</li>';
        }

        if (! empty($propertyTypeCounts['apartment'])) {
            $answer .= '<li>' . trans_choice(
                'city.FAQAnswer8Apartment',
                $propertyTypeCounts['apartment'],
                ['apartment' => $propertyTypeCounts['apartment']]
            ) . '</li>';
        }

        if ($hasOtherPropertyType) {
            $answer .= ' </ul>';
        }

        $answer .= "<p class=''>" . langGet('city.FAQAnswer8d', ['city' => $cityInfo->translation()->city]) . '</p>';

        return $answer;
    }

    protected function getAnswer9($cityInfo)
    {
        $partying = $cityInfo->getBestHostelByType('partying');
        if (! $partying) {
            return '';
        }

        $answer = langGet(
            'city.FAQAnswer9a',
            [
                'city' => $cityInfo->translation()->city,
                'HostelNamePartying' => $partying->name,
                'HostelNamePartyingLink' => $partying->getURL(),
            ]
        );

        if ($features = \App\Models\Listing\ListingFeatures::getPartyingFeatures($partying->compiledFeatures)) {
            $answer .= '</p><p><b>' . langGet('city.FAQAnswer9b') . ': </b>' . implode(', ', $features) . '.';
        }

        return $answer;
    }

    public function shouldRender()
    {
        return $this->cityInfo->hostelCount;
    }
}
