<?php

namespace Lib;

use Illuminate\Support\Facades\Validator;

/*

Custom Validation Rules

Note: When adding new validation rules, also add it to resources/lang/en/validation.php.

*/

class CustomValidations
{
    public static function addValidations()
    {
        Validator::extend('not_all_uppercase', function ($attribute, $value, $parameters, $validator) {
            return ! preg_match('/[A-Z]{5,}/', strip_tags($value)) || // Ok if it either doesn't have 5 or more capital letters in a row or,
                preg_match('/[a-z]+/', strip_tags($value)); // it has some lowercase.
        });

        Validator::extend('not_all_lowercase', function ($attribute, $value, $parameters, $validator) {
            return ! preg_match('/[a-z]{2,}/', strip_tags($value)) || // Ok if it either doesn't have 2 or more lowercase letters in a row,
                preg_match('/[A-Z]+/', strip_tags($value)); // it has some uppercase.
        });

        Validator::extend('doesnt_contain_urls', function ($attribute, $value, $parameters, $validator) {
            return stripos($value, 'http:') === false && stripos($value, 'https:') === false;
        });

        // (the built-in "size" rule doesn't check string length if it's numeric, so we are adding this specific rule.)
        Validator::extend('string_length', function ($attribute, $value, $parameters, $validator) {
            return strlen($value) == $parameters[0];
        });
        Validator::replacer('string_length', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':size', $parameters[0], $message);
        });

        Validator::extend('email_list', function ($attribute, $values, $parameters, $validator) {
            if (! is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if ($value != '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
            }

            return true;
        });

        Validator::extend('url_list', function ($attribute, $values, $parameters, $validator) {
            if (! is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if ($value != '' && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    return false;
                }
            }

            return true;
        });
    }
}
