<?php

namespace Lib;

class TranslationService
{
    // $languageFrom can be null to detect the language

    public static function translate($text, $languageFrom, $languageTo, $maxCharactersToTranslate = 5000, &$detectedLanguage = null)
    {
        if ($languageFrom == $languageTo) {
            return $text;
        }
        $text = trim(substr($text, 0, $maxCharactersToTranslate));
        if ($text == '') {
            return '';
        }
        $result = GoogleAPI::makeApiCall('https://www.googleapis.com/language/translate/v2?format=text&key=' .
            urlencode(config('custom.googleApiKey.serverSide')) . '&q=' . urlencode($text) . '&target=' . urlencode($languageTo) .
            ($languageFrom != null ? '&source=' . urlencode($languageFrom) : ''), 60 * 60);
        if ($result == '') {
            return null;
        } // probably an error occurred.
        $firstTranslation = head(json_decode($result)->data->translations);
        $detectedLanguage = (property_exists($firstTranslation, 'detectedSourceLanguage') ? $firstTranslation->detectedSourceLanguage : null);

        return $firstTranslation->translatedText;
    }
}
