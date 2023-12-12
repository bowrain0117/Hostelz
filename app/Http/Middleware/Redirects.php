<?php

namespace App\Http\Middleware;

use App\Models\Languages;
use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Redirects
{
    protected $langPrefix;

    public function __construct()
    {
        $this->langPrefix = Languages::current()->urlPrefix();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (App::runningInConsole()) {
            return $next($request);
        }

        if ($this->langPrefix === '') {
            return $this->redirectWithoutLang($request, $next);
        }

        return $this->redirectWithLang($request, $next);
    }

    protected function redirectWithoutLang(Request $request, $next)
    {
        $redirect = Redirect::where('encoded_url', rawurlencode($this->getFullUrl($request)))->first();
        if ($redirect) {
            return redirect($redirect->new_url, 301);
        }

        return $next($request);
    }

    protected function redirectWithLang(Request $request, $next)
    {
        $urlWithoutLang = $request->getSchemeAndHttpHost() . '/' . ltrim($request->getPathInfo(), $this->langPrefix);
        $redirect = Redirect::where('encoded_url', rawurlencode($urlWithoutLang))->first();
        if ($redirect) {
            $parse = parse_url($redirect->new_url);

            $redirectLink = str_replace(
                $parse['host'],
                $parse['host'] . $this->langPrefix,
                $redirect->new_url
            );

            return redirect($redirectLink, 301);
        }

        //  check with lang
        return $this->redirectWithoutLang($request, $next);
    }

    protected function getFullUrl(Request $request)
    {
        return $request->getSchemeAndHttpHost() . $_SERVER['REQUEST_URI'];
    }
}
