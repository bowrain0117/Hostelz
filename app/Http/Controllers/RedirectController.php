<?php

namespace App\Http\Controllers;

use Lib\FormHandler;

class RedirectController extends Controller
{
    public function index($pathParameters = null)
    {
        $fields = [
            'id' => [
                'isPrimaryKey' => true,
                'editType' => 'ignore',
                'searchAndListType' => 'ignore',
            ],
            'old_url' => ['validation' => 'required|url'],
            'encoded_url' => ['type' => 'hidden'],
            'new_url' => ['validation' => 'required'],
            'note' => ['type' => 'textarea', 'rows' => 1],
            'tag' => ['type' => 'hidden'],
            'type' => ['type' => 'hidden'],
        ];

        $formHandler = new FormHandler('Redirect', $fields, $pathParameters, 'App\Models');

        $formHandler->defaultInputData = [
            'tag' => 'redirect',
            'type' => '301',
        ];
        $formHandler->allowedModes = $this->getAllowedModes();
        $formHandler->showFieldnameIfMissingLang = true;
        $formHandler->listSort['created_at'] = 'desc';
        $formHandler->listPaginateItems = 50;
        $formHandler->listSelectFields = ['old_url', 'new_url', 'note'];
        $formHandler->whereData = ['tag' => 'redirect'];

        return $formHandler->go('staff.seo.redirect.index', $this->getAllowedModes(), 'searchAndList');
    }

    public function prettylink($parameters = null)
    {
        $fields = [
            'id' => [
                'isPrimaryKey' => true,
                'editType' => 'ignore',
                'searchAndListType' => 'ignore',
            ],
            'old_url' => ['validation' => 'required|url'],
            'encoded_url' => ['type' => 'hidden'],
            'new_url' => ['validation' => 'required|url'],
            'note' => ['type' => 'textarea', 'rows' => 1],
            'tag' => ['type' => 'hidden'],
            'type' => ['type' => 'hidden'],
        ];

        $formHandler = new FormHandler('Redirect', $fields, $parameters, 'App\Models');

        $formHandler->defaultInputData = [
            'tag' => 'prettylink',
            'type' => '301',
        ];

        $formHandler->allowedModes = $this->getAllowedModes();
        $formHandler->showFieldnameIfMissingLang = true;
        $formHandler->listSort['old_url'] = 'desc';
        $formHandler->listPaginateItems = 50;
        $formHandler->listSelectFields = ['old_url', 'new_url', 'note'];
        $formHandler->whereData = ['tag' => 'prettylink'];

        return $formHandler->go('staff.seo.prettylink.index', $this->getAllowedModes(), 'searchAndList');
    }

    private function getAllowedModes()
    {
        return auth()->user()->hasPermission('admin') ?
            ['searchForm', 'searchAndList', 'updateForm', 'update', 'delete', 'multiDelete', 'insert', 'insertForm'] :
            ['searchForm', 'searchAndList', 'display'];
    }
}
