<?php

namespace Lib;

use Illuminate\Support\Facades\App;

class DiskSize
{
    const MiN_SIZE = 15;    //  percentage

    const WARN_SIZE = 10;   //  percentage

    public static function getRootDiskInfo()
    {
        $info = [
            'freeSpace' => self::formatDiskSizefunction(disk_free_space('/')),
            'totalSpace' => self::formatDiskSizefunction(disk_total_space('/')),
            'picsSpace' => self::getPicsSpaces(),
        ];

        $info['freeSpacePercentage'] = number_format((float) ((float) $info['freeSpace'] / (float) $info['totalSpace'] * 100), 2, '.', '');
        $info['classTextColor'] = self::diskSpaceClassColor($info['freeSpacePercentage']);
        $info['isSendEmail'] = self::isSendEmail($info['freeSpacePercentage']);
        $info['isClearCache'] = self::isClearCache($info['freeSpacePercentage']);

        return $info;
    }

    public static function diskSpaceClassColor($percentage)
    {
        switch ($percentage) {
            case $percentage < self::WARN_SIZE:
                return 'text-danger';
            case $percentage < self::MiN_SIZE:
                return 'text-warning';
            default:
                return 'text-success';
        }
    }

    public static function formatDiskSizefunction($bytes)
    {
        $symbols = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $exp = floor(log($bytes) / log(1024));

        return sprintf('%.2f ' . $symbols[$exp], ($bytes / (1024 ** floor($exp))));
    }

    public static function isSendEmail($percentage)
    {
        return $percentage < self::WARN_SIZE;
    }

    public static function isClearCache($percentage)
    {
        return $percentage < self::MiN_SIZE;
    }

    public static function clearSystemCache($artisan)
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $artisan->call('view:clear'); // Laravel's view cache

        if (App::environment() === 'production') {
            shell_exec(
                'cd ' . base_path() . ';' .
                    'composer dump-autoload --no-dev 2>&1'
            );

            //            $artisan->call('view:cache');
            //            $artisan->call('optimize');
        }

        //        $artisan->call('optimize:clear');

        PageCache::clearAll();
    }

    private static function getPicsSpaces()
    {
        $path = config('pics.picsSpacesPath');

        return humanReadableFileSize(disk_free_space($path)) . ' / ' . humanReadableFileSize(disk_total_space($path));
    }
}
