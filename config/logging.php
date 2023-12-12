<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'error'],
            'ignore_exceptions' => false,
        ],

        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel-errors-main.log'),
            'level' => env('LOG_LEVEL_ERROR', 'error'),
            'days' => 4,
            'permission' => 0664,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0664,
        ],

        'login' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel-login.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'searchAutocomplete' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel-search-autocomplete.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'cityPageSpeed' => [
            'driver' => 'single',
            'path' => storage_path('logs/city-page-speed.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'listingAvailabilitySpeed' => [
            'driver' => 'single',
            'path' => storage_path('logs/listing-availability-speed.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'listingSaveEvent' => [
            'driver' => 'single',
            'path' => storage_path('logs/listing-save-event.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'dateTime' => [
            'driver' => 'single',
            'path' => storage_path('logs/date-time.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'import' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel-import.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'importedImport' => [
            'driver' => 'daily',
            'path' => storage_path('logs/importedImport.log'),
            'level' => 'debug',
            'bubble' => false,
            'days' => 3,
            'permission' => 0664,
        ],

        'importedStatusUpdate' => [
            'driver' => 'single',
            'path' => storage_path('logs/imported-status-update.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'importedOutdated' => [
            'driver' => 'daily',
            'path' => storage_path('logs/importedOutdated.log'),
            'level' => 'debug',
            'bubble' => false,
            'days' => 3,
            'permission' => 0664,
        ],

        'roomAvailability' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel-roomAvailability.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'days' => 2,
            'permission' => 0664,
        ],

        'websiteMaintenance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/websiteMaintenance.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'bubble' => false,
            'permission' => 0664,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 4,
            'permission' => 0664,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
