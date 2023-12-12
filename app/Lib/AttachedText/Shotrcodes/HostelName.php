<?php

namespace App\Lib\AttachedText\Shotrcodes;

use App\Models\AttachedText;
use Closure;

class HostelName
{
    public function handle(AttachedText $item, Closure $next)
    {
        $item->data = str_replace('[hostelName]', $item->nameOfSubject(), $item->data);

        return $next($item);
    }
}
