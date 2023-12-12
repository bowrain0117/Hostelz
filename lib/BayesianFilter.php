<?php

namespace Lib;

use Illuminate\Support\Facades\DB;

/*
information sources:
http://code.google.com/p/phpspamdetection/

http://www.phpclasses.org/package/4236-PHP-Detect-spam-in-text-using-Bayesian-techniques.html
http://stackoverflow.com/questions/3575626/php-implementation-of-bayes-classificator-assign-topics-to-texts
http://en.wikipedia.org/wiki/Bayesian_spam_filtering
http://www.fogcreek.com/FogBUGZ/Downloads/KamensPaper.pdf

The problem with directly using Bayes Theorem to filter spam is that despite what you may think, when filtering spam you aren't actually interested in the mathamatical probability of an email being spam.

Problems with existing Bayes Theorem based spam filters:
- Floating point underruns
- Doesn't consider how many times a word has been seen in trained emails.

We need an algorithm that will:

- Give more weight to words that have been seen in a higher number of spam/ham emails, but also limit that weight so that there is a limit to how much a single word can influence the overall spamicity score.
- Ignore words that are ambiguous (so that a large number of barely relevant words don't add up to make an email seem like ham when it's really spam (to combat Bayesian poisoning).
*/

/*

    Database table:

    // Note: token is first in the index for easy searching for '' tokens used for the total count

    CreateTable('bayesianFilter',"
    	id INT AUTO_INCREMENT NOT NULL,
    	token VARCHAR(200) NOT NULL,
    	subjectType VARCHAR(50) NOT NULL,
    	bucket VARCHAR(50) NOT NULL,
    	hits INT NOT NULL,
    	PRIMARY KEY(id), INDEX(token(20))
    ");

*/

class BayesianFilter
{
    public $minTokenLength = 1;

    public $maxTokenLength = 100;

    public $maxHitsToCountPerToken = 35; // Limits the relative impact any single token can have.

    public $defaultProbability = 0.5; // samples without enough history will be forced to drift more towards this probability

    public function resetTrainingData($subjectType)
    {
        DB::table('bayesianFilter')->where('subjectType', $subjectType)->delete();
    }

    public function knownTokensCount($subjectType = null, $bucket = null)
    {
        $query = DB::table('bayesianFilter');
        if ($subjectType !== null) {
            $query->where('subjectType', $subjectType);
        }
        if ($bucket !== null) {
            $query->where('bucket', $bucket);
        }

        return $query->count();
    }

    public function train($text, $subjectType, $tokenRegex, $bucket)
    {
        $output = '<br><b>train(text:' . htmlspecialchars($text) . ", subjectType:$subjectType, tokenRegex:$tokenRegex, bucket:$bucket)</b><br>";

        $tokens = array_map('mb_strtolower',
            $tokenRegex != '' ? preg_split($tokenRegex, $text, -1, PREG_SPLIT_NO_EMPTY) : [$text]
        );
        $tokens = array_count_values($tokens); // makes an array of token=>count

        $total = 0;
        // note we currently ignore the $count and only count each word once per text
        foreach ($tokens as $token => $count) {
            $token = convertStringToValidUTF8($token);

            $output .= "('" . htmlspecialchars($token) . "') ";

            if (mb_strlen($token) < $this->minTokenLength || mb_strlen($token) > $this->maxTokenLength) {
                continue;
            }
            $total++;
            // $token = trim($token);
            $id = DB::table('bayesianFilter')->where('subjectType', $subjectType)->where('token', $token)->where('bucket', $bucket)->value('id');

            if ($id) {
                DB::table('bayesianFilter')->where('id', $id)->increment('hits');
            } else {
                DB::table('bayesianFilter')->insert(['token' => $token, 'subjectType' => $subjectType, 'bucket' => $bucket, 'hits' => 1]);
            }
        }

        // update the total bucket counts (stored in special rows with '' as token name)
        $id = DB::table('bayesianFilter')->where('subjectType', $subjectType)->where('token', '')->where('bucket', $bucket)->value('id');
        if ($id) {
            DB::table('bayesianFilter')->where('id', $id)->increment('hits', $total);
        } else {
            DB::table('bayesianFilter')->insert(['token' => '', 'subjectType' => $subjectType, 'bucket' => $bucket, 'hits' => $total]);
        }

        return $output;
    }

    public function evaluate($text, $subjectType, $tokenRegex, $reasonableNumOfHits, &$output = '')
    {
        $output .= '<br><h3>evaluate:</h3>&nbsp;text: <b>' . htmlspecialchars($text) . "</b><br>&nbsp;subjectType: <b>$subjectType</b><br>&nbsp;tokenRegex: <b>$tokenRegex</b><br>&nbsp;reasonableNumOfHits: <b>$reasonableNumOfHits</b><br>";

        // * Set $totalTrainedFraction based on count of total trainings done on each bucket *

        $totalTrainedOnBucket = DB::table('bayesianFilter')->where('subjectType', $subjectType)->where('token', '')->pluck('hits', 'bucket')->all();

        if (! $totalTrainedOnBucket || count($totalTrainedOnBucket) < 2) {
            return false;
        } // no training has been down on this subjectType, or only one bucket type known so far
        $totalTrainedAllBuckets = array_sum($totalTrainedOnBucket);
        $totalTrainedFraction = [];

        $output .= '<br>Total trained: ';
        foreach ($totalTrainedOnBucket as $bucket => $totalTrainedThisBucket) {
            $totalTrainedFraction[$bucket] = $totalTrainedThisBucket / $totalTrainedAllBuckets;
            $output .= "(bucket '$bucket' trained fraction = $totalTrainedThisBucket / $totalTrainedAllBuckets = ${totalTrainedFraction[$bucket]}) ";
        }
        $output .= '<br>';

        // * Get the bucket info for the text tokens *

        $textData = $tokenRegex != '' ? preg_split($tokenRegex, $text, -1, PREG_SPLIT_NO_EMPTY) : [$text];

        if (! is_array($textData)) {
            $output .= '<br>No tokens in text.<br>';

            return false;
        }

        $textTokens = array_map('mb_strtolower', $textData);
        if (! $textTokens) {
            $output .= '<br>No tokens in text.<br>';

            return false;
        }

        // Remove short or long tokens

        $output .= '<br>Tokens: ';
        foreach ($textTokens as $key=>$token) {
            $token = $textTokens[$key] = convertStringToValidUTF8($token);

            $output .= '(' . htmlspecialchars($token) . ') ';
            if (mb_strlen($token) < $this->minTokenLength || mb_strlen($token) > $this->maxTokenLength) {
                unset($textTokens[$key]);
            }
        }
        $output .= '<br>';

        if (! $textTokens) {
            $output .= '<br>No tokens of the min/max token length.<br>';

            return false;
        }

        // Get token stats from bayesianFilter table

        $rows = DB::table('bayesianFilter')->where('subjectType', $subjectType)->whereIn('token', $textTokens)->select('token', 'bucket', 'hits')->get()->all();

        if (! $rows) {
            $output .= '<br>None of the tokens were found in the database.<br>';

            return false; // none of the tokens were found in the database
        }

        // Create $tokenBuckets
        $tokenBuckets = [];
        foreach ($rows as $row) {
            $hitsScaledByTrainedFraction = $row->hits * (1 - $totalTrainedFraction[$row->bucket]);
            $originalTokenBuckets[$row->token][$row->bucket] = $row->hits;
            $tokenBuckets[$row->token][$row->bucket] = $hitsScaledByTrainedFraction;
        }

        // limits how much influence a single word can have on the overall score relative to lesser known words
        // (i.e. once we've trained on a word at least this many times, that's just as significant as a word we've seen a bazillion times.)

        $totalPointsAllBuckets = 0;
        $totalPointsInBucket = [];
        foreach ($tokenBuckets as $token => $bucketHits) {
            $totalHits = array_sum($bucketHits);
            if ($totalHits > $this->maxHitsToCountPerToken) {
                $maxHitsDownScale = $this->maxHitsToCountPerToken / $totalHits;
            } else {
                $maxHitsDownScale = 1;
            }

            if (count($bucketHits) == 1) {
                $significanceScale = 1;
            } else {
                $significanceScale = (max($bucketHits) - min($bucketHits)) / $totalHits;
            }

            $output .= "<br>token '<b><i>" . htmlspecialchars($token) . "</i></b>' totalHits: $totalHits (maxHitsDownScale: " . round($maxHitsDownScale, 4) . ')';

            foreach ($bucketHits as $bucket => $hits) {
                $points = $hits;

                $output .= "<br>&nbsp;bucket: '<b>$bucket</b>' actual: " .
                        $originalTokenBuckets[$token][$bucket] . '/' . array_sum($originalTokenBuckets[$token]);
                ' scaled for total trained: ' . round($hits, 4) . '/' . round($totalHits, 4) .
                        ' = <b>' . round($hits / $totalHits, 4) . '</b>';

                // Reduce the scale of the # of points if needed to limit the max on to maxHitsToCountPerToken
                $points *= $maxHitsDownScale;
                if ($maxHitsDownScale != 1.0) {
                    $output .= ' -> max hits scaling of ' . round($maxHitsDownScale, 4) . ' = ' . round($points, 4);
                }

                $points *= $significanceScale;
                $output .= ' -> significanceScale of ' . round($significanceScale, 4) . ' to <b>' . round($points, 4) . '</b>';

                // Add the result for this word in this bucket
                if (! array_key_exists($bucket, $totalPointsInBucket)) {
                    $totalPointsInBucket[$bucket] = $points;
                } else {
                    $totalPointsInBucket[$bucket] += $points;
                }
                $totalPointsAllBuckets += $points;
            }
        }

        if (! $totalPointsAllBuckets) {
            return false;
        } // no info on these tokens

        $result = [];

        // Save the $result[] based on $points/$totalPointsAllBuckets, scaled by $totalPointsAllBuckets

        foreach ($totalPointsInBucket as $bucket => $points) {
            $basicProbability = $points / $totalPointsAllBuckets;
            $defaultProbability = 1 / count($totalTrainedOnBucket);
            $probabilityConsideringNumHits =
                ($reasonableNumOfHits * $defaultProbability + $totalPointsAllBuckets * $basicProbability) / ($reasonableNumOfHits + $totalPointsAllBuckets);
            $result[$bucket] = $probabilityConsideringNumHits;
            $output .= "<br><b>Result: bucket '$bucket'</b>: pointsThisBucket/totalPointsAllBuckets = " . round($points, 4) . '/' . round($totalPointsAllBuckets, 4) . ' = ' . round($basicProbability, 4) . ". probabilityConsideringNumHits ($reasonableNumOfHits) = <b>" . round($probabilityConsideringNumHits, 4) . '</b>';
        }

        if (! $result) {
            $output .= '<br><b>No result.</b><br>';

            return false;
        }

        // Divide any remaining percent by all other buckets

        $otherBuckets = $remainingFraction = 1;
        foreach ($totalTrainedOnBucket as $bucket => $ignored) {
            if (empty($result[$bucket])) {
                $otherBuckets++;
            } else {
                $remainingFraction -= $result[$bucket];
            }
        }
        foreach ($totalTrainedOnBucket as $bucket => $ignored) {
            if (empty($result[$bucket])) {
                $result[$bucket] = $remainingFraction / $otherBuckets;
            }
        }

        arsort($result, SORT_NUMERIC);

        $output .= "<br><b>Final Result for '$subjectType':</b><br>";
        foreach ($result as $k => $v) {
            $output .= "&nbsp;'$k' " . round($v * 100, 4) . '%<br>';
        }

        return $result;
    }

    // $weights: sourceType => array(tokenRegex, reasonableNumOfHits)
    public function trainMultipleFields($fields, $weights, $sourceTypePrepend, $isSpam)
    {
        foreach ($weights as $sourceType => $sourceParams) {
            $this->train($fields[$sourceType], $sourceTypePrepend . $sourceType, $sourceParams['tokenRegex'], $isSpam ? 'spam' : 'ham');
        }
    }

    // $weights: sourceType => array(tokenRegex, reasonableNumOfHits)
    public function evaluateMultipleFields($fields, $weights, $sourceTypePrepend, &$output = '')
    {
        $fractionTotals = $totalWeights = [];

        foreach ($weights as $sourceType => $sourceParams) {
            $results = $this->evaluate($fields[$sourceType], $sourceTypePrepend . $sourceType, $sourceParams['tokenRegex'], $sourceParams['reasonableNumOfHits'], $output);
            if (! $results) {
                continue;
            }

            if (count($results) == 1) {
                $significanceScale = 1;
            } else {
                $significanceScale = (max($results) - min($results));
            }

            $output .= "<br>Calculate total for '$sourceType': <br>";
            foreach ($results as $bucket => $score) {
                $adjustedWeight = $sourceParams['weight'] * $significanceScale;
                $output .= "&nbsp;'$bucket' score <b>" . round($score, 4) .
                    "</b>. weight $sourceParams[weight] -> significanceScale of " . round($significanceScale, 4) . ' to weight ' . round($adjustedWeight, 4) . '<br>';

                if (! array_key_exists($bucket, $fractionTotals)) {
                    $fractionTotals[$bucket] = 0;
                } // initialize
                $fractionTotals[$bucket] += $score * $adjustedWeight;

                // Note: Because evaluate() currently always returns a result for every bucket, all the $totalWeights[] values are going to be the same, but that's ok.
                if (! array_key_exists($bucket, $totalWeights)) {
                    $totalWeights[$bucket] = 0;
                } // initialize
                $totalWeights[$bucket] += $adjustedWeight;
            }
        }

        if (! $fractionTotals) {
            return false;
        }

        // Calculate weighted results

        $output .= '<h3>Final results for multipleFields:</h3>';
        $results = [];
        foreach ($fractionTotals as $bucket => $bucketFraction) {
            $results[$bucket] = $bucketFraction / $totalWeights[$bucket];
            $output .= "&nbsp;bucket '<b>$bucket</b>' fraction / weight = " . round($bucketFraction, 4) . ' / ' . round($totalWeights[$bucket], 4) . ' = <b>' . round($results[$bucket], 4) . '</b><br>';
        }
        $output .= '<br>';
        arsort($results, SORT_NUMERIC);

        return $results;
    }
}
