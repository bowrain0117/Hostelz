<?php

namespace App\Http\Controllers;

use Lib\TranslationService;

class TranslationController extends Controller
{
    public function translate()
    {
        $strings = request()->input('strings');
        $languageTo = request()->input('languageTo');
        $languageFrom = request()->input('languageFrom', 'en');

        $result = collect($strings)->map(function ($item) use ($languageFrom, $languageTo) {
            $string = trim($item['string'], '"');
            if ($string === '') {
                return false;
            }

            return [
                'id' => $item['id'],
                'string' => TranslationService::translate($string, $languageFrom, $languageTo),
            ];
        })->reject(function ($value) {
            return $value === false;
        });

        return ['result' => $result];
    }
}
