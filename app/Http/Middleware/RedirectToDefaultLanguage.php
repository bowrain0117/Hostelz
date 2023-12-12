<?php

namespace App\Http\Middleware;

use App\Models\Languages;
use Closure;
use Illuminate\Http\Request;

class RedirectToDefaultLanguage
{
    public function handle(Request $request, Closure $next)
    {
        if (Languages::isDefaultLanguage()) {
            return $next($request);
        }

        return redirect(
            $this->defaultUrl($request->fullUrl()),
            301,
            ['X-Robots-Tag' => 'noindex']
        );
    }

    private function defaultUrl($url): string
    {
        return Languages::current()->changeUrlFromThisLanguageTo($url, Languages::DEFAULT_LANG_CODE);
    }
}
