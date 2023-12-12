<?php

namespace App\Livewire\Staff\Import;

use App\Console\Commands\ImportedImport;
use App\Models\ImportHistory;
use App\Services\ImportSystems\Hostelsclub\ImportHostelsclub;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class HostelsclubImport extends Component
{
    protected bool $isActive = false;

    protected string $system;

    protected ?ImportHistory $import;

    public function boot(): void
    {
        $this->system = ImportHostelsclub::SYSTEM_NAME;
        $this->import = $this->getLastImport();
    }

    public function mount(): void
    {
        $this->isActive = $this->isActive();
    }

    public function render()
    {
        return view(
            'livewire.staff.import.hostelsclub-import',
            [
                'import' => $this->getLastImport(),
                'imports' => $this->getImports(),
                'isActive' => $this->isActive(),

                'current' => $this->getCurrent(),
                'percentage' => $this->getPercentage(),
                'maxCount' => $this->getMaxCount(),
            ]
        );
    }

    // Actions

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

        $this->import
            ->update(['cancelled_at' => now()]);

        $this->isActive = false;

        $this->dispatch('resetImport');

        $this->dispatch('import:finished', $this->system);
    }

    // Listeners

    public function importStarted($data)
    {
        $this->import->options['totalPages'] = $data['options']['totalPages'];

        $this->import->save();
    }

    public function importPageAdded($data): void
    {
        $this->import->options['lastPage'] = $data['page'];

        $this->import->save();
    }

    public function importFinished($data): void
    {
        $this->import->update(['finished_at' => now()]);

        $this->isActive = false;

        $this->dispatch('import:finished', $this->system);
    }

    public function importInserted($data): void
    {
        $this->import->increment('checked');
        if ($data['isInserted'] === true) {
            $this->import->increment('inserted');
        }
    }

    public function disableImport($system)
    {
        if ($system === $this->system) {
            return;
        }

        $this->isActive = true;
    }

    public function enableImport($system)
    {
        if ($system === $this->system) {
            return;
        }

        $this->isActive = false;
    }

    public function getListeners(): array
    {
        return [
            "echo-private:import-started.{$this->system},.import.started" => 'importStarted',
            "echo-private:import-page-added.{$this->system},.import.page.added" => 'importPageAdded',
            "echo-private:import-finished.{$this->system},.import.finished" => 'importFinished',
            "echo-private:import-inserted.{$this->system},.import.inserted" => 'importInserted',
            'import:started' => 'disableImport',
            'import:finished' => 'enableImport',

        ];
    }

    //

    private function getMaxCount(): int
    {
        return $this->import?->getOption('totalPages') ?? 0;
    }

    private function getCurrent(): int
    {
        return $this->import?->getOption('lastPage') ?? 0;
    }

    public function getPercentage(): int
    {
        $maxCount = $this->getMaxCount();
        if ($maxCount === 0) {
            return 0;
        }

        return ceil($this->getCurrent() / $maxCount * 100);
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

    private function isActive(): bool
    {
        return $this->import?->isActive() ?? false;
    }
}
