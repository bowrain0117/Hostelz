<?php

namespace App\Http\Controllers\Staff;

use App\Enums\Listing\CategoryPage;
use App\Http\Controllers\Controller;
use App\Models\AttachedText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Lib\FormHandler;

class CityCategoryPageDescriptionStaffController extends Controller
{
    public function __invoke(Request $request, $pathParameters = null)
    {
        if ($pathParameters === 'edit-or-create') {
            // This is a special URL we use that redirect to either create or edit depending on whether the text already exists yet.
            $existing = AttachedText::query()
                ->where('subjectType', 'cityInfo')
                ->where('subjectID', $request->input('id'))
                ->where('type', $request->input('type'))
                ->first();

            if ($existing) {
                return to_route('staff-cityCategoryPageDescription', $existing->id);
            }

            return to_route(
                'staff-cityCategoryPageDescription',
                [
                    'new',
                    'data' => [
                        'subjectType' => 'cityInfo',
                        'subjectID' => $request->input('id'),
                        'type' => $request->input('type'),
                        'language' => 'en',
                    ],
                ]
            );
        }

        $fieldInfo = [
            'id' => [
                'isPrimaryKey' => true,
                'type' => 'ignore',
            ],
            'subjectType' => [
                'type' => 'hidden',
                'searchFormDefaultValue' => 'CityInfo',
            ],
            'subjectID' => [
                ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
            ],
            'subject_name' => [
                'type' => 'display',
                'searchType' => 'ignore',
                'getValue' => function ($formHandler, $model) {
                    return $model->nameOfSubject();
                },
            ],
            'type' => [
                'type' => 'select',
                'options' => collect(CategoryPage::cases())->map(fn (CategoryPage $i) => $i->attachmentType())->toArray(),
                'comparisonType' => 'startsWith',
                'validation' => 'required',
            ],
            'language' => [
                'type' => 'hidden',
                'searchFormDefaultValue' => 'en',
            ],
            'data' => [
                'type' => 'WYSIWYG',
                'rows' => 20,
                'sanitize' => 'WYSIWYG',
                'fieldLabelText' => 'Text',
            ],
        ];

        $formHandler = new FormHandler(
            'AttachedText',
            $fieldInfo,
            $pathParameters,
            'App\Models'
        );

        $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'insertForm', 'insert', 'updateForm', 'update', 'delete'];
        $formHandler->whereData = ['type' => '%' . CategoryPage::TABLE_KEY];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['subjectID', 'subject_name', 'type'];
        $formHandler->listSort['id'] = 'asc';

        $formHandler->go(null, null, 'searchAndList');

        return $formHandler->display('staff/edit-cityCategoryPageDescription');
    }
}
