<?php

namespace App\Http\Controllers;

use App;
use App\Models\QuestionResult;
use App\Models\QuestionSet;
use App\Services\AjaxDataQueryHandler;
use DB;
use Lib\FormHandler;
use Request;

class QuestionsController extends Controller
{
    public function questionSets($pathParameters = null)
    {
        $formHandler = new FormHandler(
            'QuestionSet',
            QuestionSet::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['setName'];
        $formHandler->go();

        $message = '';

        if (Request::has('objectCommand') && $formHandler->model) {
            $questionSet = $formHandler->model;

            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'duplicate':
                    $duplicate = $questionSet->duplicate();
                    $message = 'Duplicate created. <a href="' . routeURL('staff-questionSets', $duplicate->id) . '">View New Question Set</a>';

                    break;

                case 'generateAskURL':
                    return routeURL(
                        'questions-ask',
                        [
                            $questionSet->id, Request::input('referenceCode'), $questionSet->questionAskVerificationCode(Request::input('referenceCode')), ],
                        'publicSite'
                    );

                    break;
            }
        }

        return $formHandler->display('staff/edit-questionSets', compact('message'));
    }

    public function questionResults($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(Request::input('userID_selectorIdFind'), Request::input('userID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler(
            'QuestionResult',
            QuestionResult::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'delete', 'multiDelete'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        //  using listDisplayFields instead of listSelectFields because we need to get the whole model to use prepResultsForDisplay() below)
        $formHandler->listDisplayFields = ['startTime', 'ipAddress', 'email', 'referenceCode', 'staffNotes'];
        $formHandler->listSort = ['startTime' => 'asc'];
        $formHandler->go();

        $extraListFields = [];
        if ($formHandler->mode == 'list' || $formHandler->mode == 'searchAndList') {
            foreach ($formHandler->list as $rowKey => $rowItem) {
                $resultsDisplay = $rowItem->prepResultsForDisplay();
                $extraListFields[$rowKey] = [$resultsDisplay['totalPoints']];
            }
        }

        return $formHandler->display('staff/edit-questionResults', compact('extraListFields'));
    }

    public function ask($questionSetID, $referenceCode, $verificationCode)
    {
        $questionSet = QuestionSet::find($questionSetID);
        if (! $questionSet) {
            App::abort(404);
        }
        if ($verificationCode != $questionSet->questionAskVerificationCode($referenceCode)) {
            return 'Invalid URL.';
        }
        if ($questionSet->requireAccess != '' && ! auth()->user()->hasPermission($questionSet->requireAccess)) {
            return accessDenied();
        }

        $questions = $questionSet->prepQuestionsForDisplay();

        $message = null;
        if (Request::has('answers')) {
            $questionResult = QuestionResult::create(['questionSetID' => $questionSetID, 'userID' => intval(auth()->id()), 'referenceCode' => $referenceCode,
                'startTime' => DB::raw('now()'), 'ipAddress' => Request::server('REMOTE_ADDR'), 'results' => Request::input('answers'), ]);
            $message = $questions['finalText'];
        }

        return view('questions-ask', $questions)->with('message', $message)->with('showPoints', false);
    }
}
