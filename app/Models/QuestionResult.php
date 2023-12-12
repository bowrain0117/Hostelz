<?php

namespace App\Models;

use Exception;
use Lib\BaseModel;

class QuestionResult extends BaseModel
{
    protected $table = 'questionResults';

    protected $guarded = [];

    public $timestamps = false;

    public function delete(): void
    {
        // foreach ($this->pics as $pic) $pic->delete();

        parent::delete();
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'questionSetID' => ['type' => 'select', 'options' => QuestionSet::pluck('id', 'setName'), 'optionsDisplay' => 'keys'],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'email' => ['maxLength' => 150],
                    'referenceCode' => ['maxLength' => 100],
                    'results' => ['type' => 'ignore', 'searchType' => 'string'],
                    'startTime' => ['searchType' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateTimeDataType'],
                    'ipAddress' => ['maxLength' => 50],
                    'staffNotes' => ['type' => 'textarea', 'rows' => 4],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $return;
    }

    /* Accessors & Mutators */

    public function getResultsAttribute($value)
    {
        return $value == '' ? [] : unserialize($value);
    }

    public function setResultsAttribute($value): void
    {
        $this->attributes['results'] = ($value ? serialize($value) : '');
    }

    /* Static */

    /* Misc */

    public function prepResultsForDisplay()
    {
        $results = $this->results;
        $answers = [];
        $categoryTotals = [];
        $totalPoints = 0;

        foreach ($this->questionSet->prepQuestionsForDisplay()['questions'] as $key => $question) {
            switch ($question['type']) {
                case 'radio': // multiple choice
                    $points = $question['answers'][$results[$key]['answer']]['points'] ?? null;
                    $answerText = $question['answers'][$results[$key]['answer']]['text'] ?? null;

                    break;

                case 'string':
                case 'textarea':
                    $points = 0;
                    $answerText = $results[$key]['answer'];
                    foreach ($question['answers'] as $answer) {
                        if ($answer['text'] == $answerText) {
                            $points = $answer['points'];
                        }
                    }

                    break;

                default:
                    continue 2;
            }

            if ($points < 0) {
                $categoryTotals[$question['category']]['negative'] = +$points;
            } elseif ($points > 0) {
                $categoryTotals[$question['category']]['positive'] = +$points;
            }

            $totalPoints += $points;

            $answers[] = [
                'question' => $question,
                'answerText' => $answerText,
                'points' => $points,
                'time' => $results[$key]['time'],
            ];
        }

        return ['answers' => $answers, 'categoryTotals' => $categoryTotals, 'totalPoints' => $totalPoints];
    }

    /* Scopes */

    /* Relationships */

    public function questionSet()
    {
        return $this->hasOne(QuestionSet::class, 'id', 'questionSetID');
    }
}
