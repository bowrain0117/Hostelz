<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportSystemException extends Exception
{
    public array $data;

    public function __construct($message, $data = [], $code = 0, $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    public function report(): void
    {
        Log::channel('import')->error($this->getMessage(), $this->context());
    }

    public function context(): array
    {
        return [
            'data' => $this->data,
            'url' => request()->fullUrl(),
            'ip' => request()->server('REMOTE_ADDR'),
            'user' => auth()->check() ? auth()->id() : 'none',
            'time' => (string) Carbon::now(),
            'trace' => $this->getTraceAsString(),
            'referrer' => request()->server('HTTP_REFERER'),
            'agent' => request()->server('HTTP_USER_AGENT'),
        ];
    }
}
