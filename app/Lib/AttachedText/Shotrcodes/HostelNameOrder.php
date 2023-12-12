<?php

namespace App\Lib\AttachedText\Shotrcodes;

use App\Models\AttachedText;
use Closure;

class HostelNameOrder
{
    public function handle(AttachedText $item, Closure $next)
    {
        $listingName = $item->nameOfSubject();
        $wordsName = collect(explode(' ', $listingName));

        if ($this->shouldReverseName($wordsName)) {
            $wordsName->prepend($wordsName->pop());
        }

        $item->data = str_replace('[hostelNameOrder]', $wordsName->join(' '), $item->data);

        return $next($item);
    }

    private function shouldReverseName($wordsName): bool
    {
        $commonWords = ['The', 'the', 'a', 'A', '&'];
        $nonCommonWordCount = $wordsName->diff($commonWords)->count();

        return $wordsName->diff($commonWords)->count() >= 3;
    }
}
