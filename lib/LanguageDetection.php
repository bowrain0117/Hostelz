<?php

namespace Lib;

class LanguageDetection
{
    const BAYESIAN_SUBJECT_NAME = 'language-detection';

    // based on http://stackoverflow.com/questions/20719145/break-the-sentence-into-words-using-a-regular-expression-preg-split
    const LANGUAGE_DETECTION_REGEX = '/\B(?=\p{Han})|\W/u';

    // $minimumConfidence - On a scale of 0-100.
    // See also the LangageDetection class.

    public static function detect($text, $minimumConfidence = 20, $maxCharactersToConsider = 1000, &$confidence = null)
    {
        $text = trim(substr($text, 0, $maxCharactersToConsider));
        if (strlen($text) < 10) {
            return null;
        }
        $result = GoogleAPI::makeApiCall('https://www.googleapis.com/language/translate/v2/detect?key=' .
            urlencode(config('custom.googleApiKey.serverSide')) . '&q=' . urlencode($text), 60 * 60);
        if ($result == '') {
            return null;
        } // probably an error occurred.
        $detections = json_decode($result)->data->detections;
        $firstResult = head(head($detections));
        $confidence = $firstResult->confidence * 100;
        if ($firstResult->confidence * 100 < $minimumConfidence) {
            return null;
        }

        return $firstResult->language;
    }

    public static function verify($text, $expectedLanguage, $minimumCertaintyPercentage = 50)
    {
        $results = with(new BayesianFilter)->evaluate($text, self::BAYESIAN_SUBJECT_NAME,
            self::LANGUAGE_DETECTION_REGEX,
            7);

        if (! $results) {
            return '';
        }
        $certainty = reset($results) * 100;
        if ($certainty < $minimumCertaintyPercentage) {
            return '';
        }

        return key($results);
    }

    /*
    public static function detect($text, $minimumCertaintyPercentage = 50)
    {
    // Detects the language of a block of text.  Same as TranslationService::detectLanguage(), except returns our language code.
    $langCode = TranslationService::detectLanguage($text, $minimumConfidence, $maxCharactersToConsider, $confidence);
    if ($langCode == null) return null;
    $language = self::getFromOtherCodeStandard('Google', $langCode);
    if (!$language) return null;
    return $language->languageCode;
    }
    */

    public static function train($text, $languageCode)
    {
        with(new BayesianFilter)->train($text, self::BAYESIAN_SUBJECT_NAME,
            self::LANGUAGE_DETECTION_REGEX,
            $languageCode);
    }

    public static function resetTrainingData()
    {
        with(new BayesianFilter)->resetTrainingData(self::BAYESIAN_SUBJECT_NAME);
    }

    public static function knownTokensCount($language = null)
    {
        with(new BayesianFilter)->knownTokensCount(self::BAYESIAN_SUBJECT_NAME, $language);
    }
}
