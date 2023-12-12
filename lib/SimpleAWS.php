<?php

namespace Lib;

use Exception;

/*
Wasn't able to get this to work.  Was getting a "AuthFailure" response from Amazon.  But may have just been an issue with the AWIS service.
*/

class SimpleAWS
{
    /*
        Based on some code from http://stackoverflow.com/questions/12275987/aws-signature-creation-using-php.
    */

    public static function makeSignatureVersion2Request($host, $uri, $extraParams)
    {
        $private_key = env('AWS_SECRET');

        $method = 'GET';

        $params = [
            'AWSAccessKeyId' => env('AWS_KEY'),
            'Timestamp' => gmdate("Y-m-d\TH:i:s\Z"),
            'SignatureMethod' => 'HmacSHA256',
            'SignatureVersion' => '2',
        ];

        foreach ($extraParams as $param => $value) {
            $params[$param] = $value;
        }

        ksort($params);

        // sort the parameters
        // create the canonicalized query
        $canonicalized_query = [];
        foreach ($params as $param => $value) {
            $param = str_replace('%7E', '~', rawurlencode($param));
            $value = str_replace('%7E', '~', rawurlencode($value));
            $canonicalized_query[] = $param . '=' . $value;
        }
        $canonicalized_query = implode('&', $canonicalized_query);

        // create the string to sign
        $string_to_sign =
            $method . "\n" .
            $host . "\n" .
            $uri . "\n" .
            $canonicalized_query;
        //dd($string_to_sign);

        // calculate HMAC with SHA256 and base64-encoding
        $signature = base64_encode(
            hash_hmac('sha256', $string_to_sign, $private_key, true));

        // encode the signature for the equest
        $signature = str_replace('%7E', '~', rawurlencode($signature));

        // Put the signature into the parameters
        $params['Signature'] = $signature;
        uksort($params, 'strnatcasecmp');

        // TODO: the timestamp colons get urlencoded by http_build_query
        //       and then need to be urldecoded to keep AWS happy. Spaces
        //       get reencoded as %20, as the + encoding doesn't work with
        //       AWS
        $query = urldecode(http_build_query($params));
        $query = str_replace(' ', '%20', $query);

        $string_to_send = 'http://' . $host . $uri . '?' . $query;

        return $string_to_send;
    }
}
