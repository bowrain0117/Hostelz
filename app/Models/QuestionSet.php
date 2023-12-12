<?php

namespace App\Models;

use Exception;
use Lib\BaseModel;

class QuestionSet extends BaseModel
{
    protected $table = 'questionSets';

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
                    'setName' => ['maxLength' => 100],
                    'requireAccess' => ['maxLength' => 100],
                    'questions' => ['type' => 'textarea', 'rows' => 15],
                    'notes' => ['type' => 'textarea', 'rows' => 4],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $return;
    }

    /* Accessors & Mutators */

    /* Misc */

    public function duplicate()
    {
        $new = $this->replicate();
        $new->save(); // have to save it first so that it has an id
        $newID = $new->id;

        // We have to re-load the new because the replicate one thinks it has relationships of the original that it doesn't actually have yet (Laravel bug?).
        $new = $new->fresh();

        return $new;
    }

    // used to verify that we're giving them access to it for a particular $referenceCode
    public function questionAskVerificationCode($referenceCode)
    {
        return crc32($referenceCode . '9e9898sdaFiA' . $this->setName);
    }

    public function prepQuestionsForDisplay()
    {
        $result = [];

        $questionText = str_replace("\r", '', $this->questions);
        $questionText = str_replace("\n\n\n", "\n\n", $questionText); // (triple spaces might be used for organization of question groups)
        $questionText = explode("\n\n", $questionText);

        // last question is text shown after the results are submitted
        $result['finalText'] = $questionText[count($questionText) - 1];
        unset($questionText[count($questionText) - 1]);

        $result['questionCount'] = 0;
        $questions = [];
        foreach ($questionText as $question) {
            $size = null;
            $questionLines = explode("\n", $question);

            $questionText = $questionLines[1];
            // Make "[...]" URLs into links
            $questionText = preg_replace('`(\[)(.+)(\])`', '<a href="$2" target="_blank">$2</a>', $questionText);

            $answerStrings = $questionLines[2] ?? null;
            $answers = [];
            if ($answerStrings != '') {
                if (substr($answerStrings, 0, 1) == '[') {
                    preg_match('/\[(.*)\](.*)/', $answerStrings, $matches);
                    if ($matches[1] == '*') {
                        $questionType = 'textarea';
                    } else {
                        $questionType = 'string';
                        $size = $matches[1];
                    }
                    $answerStrings = $matches[2]; // the rest of the string
                } else {
                    $questionType = 'radio';
                }
                $answerStrings = explode(';', $answerStrings);
                foreach ($answerStrings as $answerString) {
                    if ($answerString == '') {
                        continue;
                    }
                    preg_match('/\((-?\d*)\)(.*)$/', $answerString, $matches);
                    if (count($matches) != 3) {
                        throw new Exception("Bad answer format '$answerString'.");
                    }
                    $answers[] = ['points' => $matches[1], 'text' => $matches[2]];
                }
            } else {
                $questionType = '';
            }

            if ($questionType != '') {
                $result['questionCount']++;
            }

            $result['questions'][] = [
                'category' => $questionLines[0],
                'questionText' => $questionText,
                'type' => $questionType,
                'size' => $size, // only used by string input.
                'answers' => $answers,
            ];
        }

        return $result;
    }

    /* Scopes */

    /* Relationships */
}
