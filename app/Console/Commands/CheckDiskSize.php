<?php

namespace App\Console\Commands;

use App\facades\Emailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lib\DiskSize;

class CheckDiskSize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostelz:checkDiskSize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for checking the disk size';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $diskInfo = DiskSize::getRootDiskInfo();

        $infoText = "CheckDiskSize: {$diskInfo['freeSpacePercentage']}%; {$diskInfo['freeSpace']}/{$diskInfo['totalSpace']}";

        Log::notice(date('Y-m-d H:i:s') . ' ' . $infoText);

        if ($diskInfo['isClearCache']) {
            logError($infoText);
            Emailer::send(['dev.skhablyuk@gmail.com'], 'CheckDiskSize Error', 'generic-email', ['text' => $infoText]);
            DiskSize::clearSystemCache($this);
        }

        if ($diskInfo['isSendEmail']) {
            Log::warning($infoText);
            Emailer::send(['dev.skhablyuk@gmail.com'], 'CheckDiskSize Warning', 'generic-email', ['text' => $infoText]);
        }
    }
}
