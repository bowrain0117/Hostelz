<?php

namespace App\Livewire\Staff\Import;

use App\Console\Commands\ImportedImport;
use App\Models\ImportHistory;
use App\Services\ImportSystems\BookingDotCom\ImportBookingDotCom;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class BdcImport extends Component
{
    protected bool $isActive = false;

    protected string $system;

    protected ?ImportHistory $import;

    public function boot(): void
    {
        $this->system = ImportBookingDotCom::SYSTEM_NAME;
        $this->import = $this->getLastImport();
    }

    public function mount(): void
    {
        $this->isActive = $this->isActive();
    }

    public function render()
    {
        return view(
            'livewire.staff.import.bdc-import',
            [
                'import' => $this->getLastImport(),
                'imports' => $this->getImports(),
                'current' => $this->getCurrent(),
                'percentage' => $this->getPercentage(),
                'maxCount' => $this->getAll(),
                'currentBatch' => $this->getCurrentBatch(),
                'percentageCurrentBatch' => $this->getPercentageCurrentBatch(),
                'isActive' => $this->isActive(),
            ]
        );
    }

    //  Actions

    public function startImport(bool $isTest = false): void
    {
        if ($this->isActive) {
            return;
        }

        $this->isActive = true;

        $this->dispatch('import:started', $this->system);

        ImportHistory::init($this->system);

        Artisan::call(ImportedImport::class, ['system' => $this->system, '--testRun' => $isTest]);
    }

    public function resetImport(): void
    {
        if (! $this->import) {
            return;
        }

        $this->getCurrentBatch()?->cancel();

        cache()->tags('imported')->put('bdcImportBatchItem', null);
        cache()->tags('imported')->put('countriesBookingDotComImport', collect());

        $this->import
            ->update(['cancelled_at' => now()]);

        $this->isActive = false;

        $this->dispatch('resetImport');

        $this->dispatch('import:finished', $this->system);
    }

    //  Listeners

    public function disableImport($system): void
    {
        if ($system === $this->system) {
            return;
        }

        $this->isActive = true;
    }

    public function enableImport($system): void
    {
        if ($system === $this->system) {
            return;
        }

        $this->isActive = false;
    }

    public function importStarted($data): void
    {
        $this->import->options['totalPages'] = $data['options']['totalPages'];

        $this->import->save();
    }

    public function importInserted($data): void
    {
        $this->import->increment('checked');
        if ($data['isInserted'] === true) {
            $this->import->increment('inserted');
        }
    }

    public function importPageAdded($data): void
    {
        $this->import->options['lastPage'] = $this->getAll() - $data['page'];

        $this->import->save();
    }

    public function importFinished($data): void
    {
        $this->import->update(['finished_at' => now()]);

        $this->isActive = false;

        $this->dispatch('import:finished', $this->system);
    }

    public function getListeners(): array
    {
        return [
            'echo-private:import-update,.import.update' => '$refresh',
            'import:started' => 'disableImport',
            'import:finished' => 'enableImport',
        ];
    }

    //

    public function getPercentage(): int
    {
        $started = $this->getAll();
        if ($started === 0) {
            return 0;
        }

        return ceil($this->getCurrent() / $started * 100);
    }

    public function getPercentageCurrentBatch(): int
    {
        return $this->getCurrentBatch()?->progress() ?? 0;
    }

    private function getCurrentBatch(): ?Batch
    {
        $cached = cache()->tags('imported')->get('bdcImportBatchItem');
        if (empty($cached->batchId)) {
            return null;
        }

        return Bus::findBatch($cached->batchId);
    }

    protected function getLastImport(): ?ImportHistory
    {
        return ImportHistory::lastBySystem($this->system)
            ->first();
    }

    private function getImports()
    {
        return ImportHistory::lastBySystem($this->system)
            ->take(10)
            ->get();
    }

    private function getCurrent(): int
    {
        return $this->import?->getOption('lastPage') ?? 0;
    }

    private function getAll(): int
    {
        return $this->import?->getOption('totalPages') ?? 0;
    }

    private function isActive(): bool
    {
        return $this->import?->isActive() ?? false;
    }
}
