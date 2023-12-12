<?php

namespace App\Console\Commands;

use App\Services\ImportSystems\Import;
use Illuminate\Console\Command;

class ImportedImport extends Command
{
    protected $signature = 'hostelz:importedImport {system?} {--testRun}';

    protected $description = 'Import imported data from import systems.';

    public function handle(): void
    {
        $systemName = $this->argument('system') ?? '';
        $isTestRun = $this->option('testRun');

        if (! $isTestRun) {
            $result = acquireLock('importSystemData', 12 * 60 * 60, 0);
            if (! $result) {
                logWarning("Can't acquire lock.");

                return;
            }
        }

        Import::fetchSystemsData($systemName, $isTestRun);

        releaseLock('importSystemData');
    }
}
