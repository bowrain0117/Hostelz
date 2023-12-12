<?php

namespace App\Helpers;

class HttpHelper
{
    public static function addProxyOption(array $option): array
    {
        $option['proxy'] = config('custom.httpProxy');

        return  $option;
    }
}
