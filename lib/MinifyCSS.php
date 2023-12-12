<?php

/*  */

namespace Lib;

class MinifyCSS extends \MatthiasMullie\Minify\CSS
{
    public static function minifyString($content)
    {
        return with(new self($content))->minify();
    }
}
