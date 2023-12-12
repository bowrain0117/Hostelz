<?php

use App\Models\CityInfo;

if (! $cityInfo->hostelCount) {
    return;
}

$FAQs = getFAQs($cityInfo, $priceAVG);
?>

<div class="faq-wrap mb-3 mb-lg-5 pb-3 pb-lg-5 border-bottom">
	<h2 class="sb-title cl-text d-none d-lg-block"
	    id="faq">{!! langGet('city.FAQTitle', [ 'city' => $cityInfo->translation()->city]) !!}</h2>

	<p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed"
	   data-toggle="collapse" href="#faq-content">
		{!! langGet('city.FAQTitle', [ 'city' => $cityInfo->translation()->city]) !!}
		<i class="fas fa-angle-down float-right"></i>
		<i class="fas fa-angle-up float-right"></i>
	</p>

	<div class="mt-3 collapse d-lg-block" id="faq-content">
		<p class="tx-small">{!! langGet('city.FAQText', [ 'city' => $cityInfo->translation()->city]) !!}</p>

		<div class="row">

            <?php
            $schema = [
                '@context' => "https://schema.org",
                '@type' => "FAQPage",
                'mainEntity' => collect($FAQs)->reduce(function ($carry, $item) {
                    if (! $item['answer']) {
                        return $carry;
                    }

                    $carry[] = [
                        "@type" => "Question",
                        "name" => clearTextForSchema($item['question']),
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => clearTextForSchema($item['answer']),
                        ]
                    ];

                    return $carry;
                }, [])
            ];
            ?>

			<script type="application/ld+json">
				{!! json_encode($schema, JSON_PRETTY_PRINT) !!}
			</script>

			<div class="col-12">
				<div id="accordion" role="tablist">
					@foreach ($FAQs as $faq)
						@if($faq['answer'])
							<div class="card border-0 mb-4 pb-2">
								<div id="heading{{ $loop->index }}" role="tab" class="tx-body font-weight-600">
									<a data-toggle="collapse" href="#collapse{{ $loop->index }}" aria-expanded="false"
									   aria-controls="collapseOne"
									   class="accordion-link collapsed cl-text py-0 collapse-arrow-wrap collapsed">
										{!! $faq['question'] !!}
										<i class="fas fa-angle-down float-right"></i>
										<i class="fas fa-angle-up float-right"></i>
									</a>
								</div>
								<div id="collapse{{ $loop->index }}" role="tabpanel"
								     aria-labelledby="heading{{ $loop->index }}" data-parent="#accordion"
								     class="collapse mt-2">
									<div class="tx-body cl-text text-content">{!! $faq['answer'] !!}</div>
								</div>
							</div>
						@endif
					@endforeach
				</div>
			</div>
		</div>
	</div>
</div>

<?php

function getFAQs(CityInfo $cityInfo, $priceAVG)
{
    $couples = $cityInfo->getBestHostelByType('couples');
    $families = $cityInfo->getBestHostelByType('families');
    $groups = $cityInfo->getBestHostelByType('groups');

    return [
        [
            'question' => langGet('city.FAQQuestion1', ['city' => $cityInfo->translation()->city]),
            'answer' => getAnswer1($cityInfo, $priceAVG)
        ],
        [
            'question' => langGet('city.FAQQuestion2', ['city' => $cityInfo->translation()->city]),
            'answer' => getAnswer2($cityInfo)
        ],
        [
            'question' => langGet('city.FAQQuestion3', ['city' => $cityInfo->translation()->city]),
            'answer' => ($couples) ? langGet(
                'city.FAQAnswer3',
                [
                    'city' => $cityInfo->translation()->city,
                    'HostelNameCouples' => $couples->name,
                    'HostelNameCouplesLink' => $couples->getURL()
                ]
            ) : ''
        ],
        [
            'question' => langGet('city.FAQQuestion4', ['city' => $cityInfo->translation()->city]),
            'answer' => ($families) ? langGet(
                'city.FAQAnswer4',
                [
                    'city' => $cityInfo->translation()->city,
                    'HostelNameFamilies' => $families->name,
                    'HostelNameFamiliesLink' => $families->getURL()
                ]
            ) : ''
        ],
        [
            'question' => langGet('city.FAQQuestion5', ['city' => $cityInfo->translation()->city]),
            'answer' => ($groups) ? langGet(
                'city.FAQAnswer5',
                [
                    'city' => $cityInfo->translation()->city,
                    'HostelNameGroups' => $groups->name,
                    'HostelNameGroupsLink' => $groups->getURL()
                ]
            ) : ''
        ],
        [
            'question' => langGet('city.FAQQuestion6', ['city' => $cityInfo->translation()->city]),
            'answer' => (0) ? langGet('city.FAQAnswer6') : ''
        ],
        [
            'question' => langGet('city.FAQQuestion7', ['city' => $cityInfo->translation()->city]),
            'answer' => getAnswer7($cityInfo)
        ],
        [
            'question' => langGet('city.FAQQuestion8', ['city' => $cityInfo->translation()->city]),
            'answer' => getAnswer8($cityInfo)
        ],
        [
            'question' => langGet('city.FAQQuestion9', ['city' => $cityInfo->translation()->city]),
            'answer' => getAnswer9($cityInfo)
        ],
    ];
}


function getAnswer1(CityInfo $cityInfo, $priceAVG)
{
    if (
        ! $priceAVG ||
        (intval($priceAVG['dorm']) === 0 || intval($priceAVG['private']) === 0)
    ) {
        return '';
    }

    return langGet(
        'city.FAQAnswer1',
        [
            'city' => $cityInfo->translation()->city,
            'AverageDormPrice' => $priceAVG['dorm'],
            'AveragePrivatePrice' => $priceAVG['private']
        ]
    );
}

function getAnswer2(CityInfo $cityInfo)
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
                'HostelCount' => $cityInfo->hostelCount
            ]
        );
    }

    return langGet(
        'city.FAQAnswer2',
        ['city' => $cityInfo->translation()->city, 'HostelCount' => $cityInfo->hostelCount]
    );
}

function getAnswer7(CityInfo $cityInfo)
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
                'HostelNameFemaleSolo1' => $best2FST[0]->name
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
                'HostelNameFemaleSolo2' => $best2FST[1]->name
            ]
        );
    }

    return '';
}

function getAnswer8(CityInfo $cityInfo)
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
                'HostelCount' => $cityInfo->hostelCount
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
        $answer .= "<li>" . trans_choice(
                'city.FAQAnswer8Hotel',
                $propertyTypeCounts['hotel'],
                ['hotels' => $propertyTypeCounts['hotel']]
            ) . "</li>";
    }

    if (! empty($propertyTypeCounts['guesthouse'])) {
        $answer .= "<li>" . trans_choice(
                'city.FAQAnswer8Guesthouses',
                $propertyTypeCounts['guesthouse'],
                ['guesthouse' => $propertyTypeCounts['guesthouse']]
            ) . "</li>";
    }

    if (! empty($propertyTypeCounts['apartment'])) {
        $answer .= "<li>" . trans_choice(
                'city.FAQAnswer8Apartment',
                $propertyTypeCounts['apartment'],
                ['apartment' => $propertyTypeCounts['apartment']]
            ) . "</li>";
    }

    if ($hasOtherPropertyType) {
        $answer .= " </ul>";
    }

    $answer .= "<p class=''>" . langGet('city.FAQAnswer8d', ['city' => $cityInfo->translation()->city]) . "</p>";

    return $answer;
}

function getAnswer9(CityInfo $cityInfo)
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
            'HostelNamePartyingLink' => $partying->getURL()
        ]
    );

    if ($features = \App\Models\Listing\ListingFeatures::getPartyingFeatures($partying->compiledFeatures)) {
        $answer .= "</p><p><b>" . langGet('city.FAQAnswer9b') . ": </b>" . implode(', ', $features) . ".";
    }

    return $answer;
}
?>
