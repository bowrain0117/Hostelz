<?php

namespace App\Exceptions;

use App\Helpers\LegacyWebsite;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Throwable;

/*

    Note:  See also "Log::listen()" calls in EventServiceProvider.php!

*/

class Handler extends ExceptionHandler
{
    public function report(Throwable $e)
    {
        if ($this->shouldReport($e)) { // (shouldReport() only ignores any excepts defined above in $dontReport)
            $exceptionNameParts = explode('\\', get_class($e));
            $message = $e->getMessage();
            $message = end($exceptionNameParts) . ($message !== '' ? " '$message' " : '') . ' (' . $e->getFile() . ' Line: ' . $e->getLine() . ')';

            try {
                $context = [
                    'url' => Request::fullUrl(),
                    'referrer' => Request::server('HTTP_REFERER'),
                    'ip' => Request::server('REMOTE_ADDR'),
                    'agent' => Request::server('HTTP_USER_AGENT'),
                    'user' => auth()->check() ? auth()->user()->id : 'none',
                    'time' => (string) Carbon::now(),
                ];
            } catch (\Exception $exception) {
                $context = [
                    'time' => (string) Carbon::now(),
                ];
            }

            Log::error($message, $context);
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {
        $statusCode = $this->isHttpException($e) ? $e->getStatusCode() : null;

        if ($statusCode === 404) {
            if ($return = LegacyWebsite::checkForOldURLs($request)) {
                return $return;
            }
        }

        if (! App::runningInConsole() && ($statusCode === 404 || App::environment('production'))) {
            return Response::view('error', ['statusCode' => $statusCode], $statusCode === 404 ? 404 : 500); // this could eventually be moved to befor the Log call if we don't want to log these.
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return accessDenied();
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });
    }
}
