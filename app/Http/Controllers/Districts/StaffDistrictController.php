<?php

namespace App\Http\Controllers\Districts;

use App\Enums\District\Type;
use App\Http\Controllers\Controller;
use App\Models\District;
use Lib\FormHandler;

class StaffDistrictController extends Controller
{
    public function index($pathParameters = null)
    {
        $fields = [
            'id' => [],
            'slug' => [],
            'type' => ['type' => 'select', 'options' => Type::getValues()],
            'is_active' => ['type' => 'checkbox', 'value' => true, 'checkboxText' => ' '],
            'city' => [
                'getValue' => function ($formHandler, $model) {
                    return $model?->city->city;
                },
                'searchQuery' => function ($formHandler, $query, $value) {
                    return $value
                        ? $query->whereRelation('city', 'city', 'like', "%{$value}%")
                        : $query;
                },
                'orderBy' => function ($formHandler, $query, $sortDirection) {
                    return $query->select('districts.*')
                        ->join('cityInfo', 'cityInfo.id', '=', 'districts.cityId')
                        ->orderBy('cityInfo.city', $sortDirection);
                },
            ],
        ];

        $formHandler = new FormHandler('District', $fields, $pathParameters, 'App\Models');

        $formHandler->allowedModes = ['searchForm', 'searchAndList'];
        $formHandler->showFieldnameIfMissingLang = true;

        $formHandler->listPaginateItems = 20;
        $formHandler->listDisplayFields = ['id', 'slug', 'type', 'city', 'is_active'];
        $formHandler->callbacks['listRowLink'] = fn ($formHandler, $row) => $row->pathEdit;

        return $formHandler->go(view: 'districts.staff.index', defaultMode: 'searchAndList');
    }

    public function edit(District $district)
    {
        return view(
            'districts.staff.edit',
            compact('district')
        );
    }
}
