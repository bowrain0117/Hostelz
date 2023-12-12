<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\EventLog;
use App\Http\Controllers\Controller;
use App\Models\AttachedText;
use App\Services\AjaxDataQueryHandler;
use Illuminate\Http\Request;
use Lib\FormHandler;
use Lib\PageCache;

class AttachedTextStaffController extends Controller
{
    public function __invoke(Request $request, $pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            $request->input('userID_selectorIdFind'),
            $request->input('userID_selectorSearch')
        );
        if ($response !== null) {
            return $response;
        }

        $originalText = '';
        if ((int) $pathParameters) {
            $originalText = AttachedText::find($pathParameters)->data;
        }

        $formHandler = new FormHandler(
            'AttachedText',
            AttachedText::fieldInfo(auth()->user()?->isAdmin() ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );

        if (auth()->user()?->isAdmin()) {
            $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'display',
                'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete', ];
        } else {
            $formHandler->allowedModes = auth()->user()->hasPermission('staffEditAttached') ?
                ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'] :
                ['searchForm', 'list', 'searchAndList', 'display'];
        }

        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = auth()->user()?->isAdmin()
            ? ['status', 'subject_name', 'subjectType', 'subjectID', 'subjectString', 'type', 'source', 'language', 'userID']
            : ['status', 'subject_name', 'subjectType', 'subjectID', 'subjectString', 'type', 'language'];
        $formHandler->listSort['id'] = 'asc';
        $formHandler->go(null, null, 'searchAndList');

        // Special handling for place descriptions
        if (
            $formHandler->mode === 'update'
            && in_array($formHandler->model->subjectType, ['cityInfo', 'countryInfo'])
            && $formHandler->model->type === 'description'
        ) {
            if ($formHandler->model->status === 'returned') {
                // Reset the lastUpdate so they have more time to do something with it before it expires and gets deleted
                $formHandler->model->lastUpdate = date('Y-m-d');
                $formHandler->model->save();
            }

            if ($formHandler->model->dataBeforeEditing === '' && $formHandler->model->data != $originalText) {
                $formHandler->model->dataBeforeEditing = $originalText;
                $formHandler->model->save();
            }

            if ($formHandler->model->status === 'ok' && ! EventLog::where('subjectID', $formHandler->model->id)
                    ->where('subjectType', 'AttachedText')->where('action', 'accepted')->exists()) {
                // Log the event in the user's log so we know to pay them for the place description
                if ($formHandler->model->subjectType === 'cityInfo') {
                    $subjectString = 'cityInfo description';
                } elseif ($formHandler->model->subjectType === 'countryInfo' && $formHandler->model->subjectString == '') {
                    $subjectString = 'country description';
                } elseif ($formHandler->model->subjectType === 'countryInfo' && $formHandler->model->subjectString != '') {
                    $subjectString = 'region description';
                } // (it could also be a cityGroup description, but they pay the same)
                else {
                    throw new Exception("There shouldn't be any other possibilities.");
                }

                EventLog::log(
                    'staff',
                    'accepted',
                    'AttachedText',
                    $formHandler->model->id,
                    $subjectString,
                    '',
                    $formHandler->model->userID
                );

                PageCache::clearByTag('city:aggregation'); // clear cached pages related to all cities.
            }
        }

        return $formHandler->display('staff/edit-attachedText');
    }
}
