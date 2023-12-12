<?php

namespace App\Lib\Common\Ota\OtaLinks;

readonly class OtaLink
{
    public function __construct(
        public string $name,
        public string $shortName,
        public string $link,
    ) {
    }
}
