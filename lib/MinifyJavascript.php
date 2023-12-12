<?php

/*  */

namespace Lib;

class MinifyJavascript extends \MatthiasMullie\Minify\JS
{
    public static function minifyString($content)
    {
        return with(new self($content))->minify();
    }
}
