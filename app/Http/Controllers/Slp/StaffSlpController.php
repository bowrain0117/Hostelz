<?php

namespace App\Http\Controllers\Slp;

use App\Enums\CategorySlp;
use App\Enums\StatusSlp;
use App\Http\Controllers\Controller;
use App\Models\CityInfo;
use App\Models\SpecialLandingPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Lib\FormHandler;

class StaffSlpController extends Controller
{
    public function index($pathParameters = null)
    {
        $fields = [
            'id' => [],
            'slug' => [],
            'category' => ['type' => 'select', 'options' => CategorySlp::values()],
            'status' => ['type' => 'select', 'options' => StatusSlp::values()],
            'city' => [
                'getValue' => function ($formHandler, $model) {
                    $model->load('subjectable');

                    return $model->subjectable?->city;
                },
                'searchQuery' => function ($formHandler, $query, $value) {
                    $str = Str::slug($value, '_');

                    return $value
                        ? $query->whereRelation('subjectable', 'city', 'like', "%{$str}%")
                        : $query;
                },
                'orderBy' => function ($formHandler, $query, $sortDirection) {
                    return $query->orderBy('slug', $sortDirection);
                },
            ],
            'unique hostel text' => [
                'getValue' => function ($formHandler, SpecialLandingPage $model) {
                    $isNotAllTexts = $model->uniqueTextListingsNumber < $model->number_featured_hostels;
                    $class = $isNotAllTexts ? 'bg-row-danger' : '';

                    return "<span class='{$class}'>{$model->uniqueTextListingsNumber} / {$model->number_featured_hostels}</span>";
                },
                'userSortable' => false,
                'searchType' => 'ignore',
            ],
            'author' => [
                'getValue' => function ($formHandler, $model) {
                    $model->load('author');
                    if (! $model->author) {
                        return '-';
                    }

                    return "{$model->author->nickname} ({$model->author->username})";
                },
                'orderBy' => function ($formHandler, $query, $sortDirection) {
                    return $query->select('special_landing_pages.*')
                        ->join('users', 'users.id', '=', 'special_landing_pages.user_id')
                        ->orderBy('users.nickname', $sortDirection);
                },
            ],
            'created at' => [
                'getValue' => function ($formHandler, $model) {
                    return $model->created_at->diffForHumans();
                },
                'orderBy' => function ($formHandler, $query, $sortDirection) {
                    return $query->orderBy('created_at', $sortDirection);
                },
            ],
            'missing_unique_text' => [
                'type' => 'ignore',
                'searchType' => 'checkbox',
                'value' => true,
                'fieldLabelText' => 'SLP With Missing Unique Hostel Text',
                'checkboxText' => '',
                'searchQuery' => function ($formHandler, $query, $value) {
                    if (! $value) {
                        return $query;
                    }

                    $formHandler->callbacks['beforeRunQuery'] = function ($formHandler) {
                        $itemsWithOutUniqueText = $formHandler->query->get()
                            ->filter(fn (SpecialLandingPage $slp) => $slp->uniqueTextListingsNumber < $slp->number_featured_hostels);

                        $formHandler->query = $formHandler->query->whereIn('id', $itemsWithOutUniqueText->pluck('id')->toArray());
                    };

                    return $query;
                },
            ],
        ];

        $formHandler = new FormHandler(
            'SpecialLandingPage',
            $fields,
            $pathParameters,
            'App\Models'
        );

        $formHandler->allowedModes = ['searchForm', 'searchAndList'];
        $formHandler->showFieldnameIfMissingLang = true;

        $formHandler->listPaginateItems = 20;
        $formHandler->listDisplayFields = ['author', 'city', 'status', 'category', 'slug', 'created at', 'unique hostel text'];
        $formHandler->callbacks['listRowLink'] = fn ($formHandler, $row) => $row->pathEdit;

        return $formHandler->go(view: 'slp.staff.index', defaultMode: 'searchAndList');
    }

    public function edit(SpecialLandingPage $slp, $language = 'en')
    {
        return view(
            'slp.staff.edit',
            compact('slp')
        );
    }

    public function create(?CityInfo $city)
    {
        $slp = SpecialLandingPage::newModelInstance([
            'user_id' => auth()?->user()->id ?? '',
            'status' => StatusSlp::Draft,
            'category' => CategorySlp::Best,
            'number_featured_hostels' => SpecialLandingPage::FEATURED_HOSTELS,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slp->subjectable()->associate($city);

        return view(
            'slp.staff.edit',
            compact('slp')
        );
    }
}
