<?php

namespace App\Livewire\Staff\Import;

use App\Console\Commands\ImportedImport;
use App\Models\ImportHistory;
use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class BookHostelsImport extends Component
{
    protected bool $isActive = false;

    protected string $system;

    protected ?ImportHistory $import;

    public function boot(): void
    {
        $this->system = ImportBookHostels::SYSTEM_NAME;
        $this->import = $this->getLastImport();
    }

    public function mount(): void
    {
        $this->isActive = $this->isActive();
    }

    public function render()
    {
        return view(
            'livewire.staff.import.book-hostels-import',
            [
                'import' => $this->import,
                'imports' => $this->getImports(),
                'current' => $this->getCurrent(),
                'percentage' => $this->getPercentage(),
                'maxCount' => $this->getMaxCount(),
                'isActive' => $this->isActive(),
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

        Artisan::queue(ImportedImport::class, ['system' => 'BookHostels', '--testRun' => $isTest]);
    }

    public function resetImport(): void
    {
        if (! $this->import) {
            return;
        }

        $this->getBatch()?->cancel();

        $this->import
            ->update(['cancelled_at' => now()]);

        $this->isActive = false;

        $this->dispatch('resetImport');

        $this->dispatch('import:finished', $this->system);
    }

    private function getBatch(): ?Batch
    {
        return Bus::findBatch(
            $this->import?->options['batchId'] ?? ''
        );
    }

    private function getCurrent(): int
    {
        return $this->import?->getOption('lastPage') ?? 0;
    }

    private function getPercentage(): int
    {
        $totalPages = $this->getMaxCount();
        if ($totalPages === 0) {
            return 0;
        }

        return (int) ($this->getCurrent() / $totalPages * 100);
    }

    private function getMaxCount(): int
    {
        return $this->import?->getOption('totalPages') ?? 0;
    }

    private function isActive(): bool
    {
        return $this->import?->isActive() ?? false;
    }

    // Import

    private function getLastImport(): ?ImportHistory
    {
        return ImportHistory::lastBySystem($this->system)
            ->first();
    }

    private function getImports(): Collection
    {
        return ImportHistory::lastBySystem($this->system)
            ->take(10)
            ->get();
    }

    // Listeners

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

    public function getListeners(): array
    {
        return [
            'echo-private:import-update,.import.update' => '$refresh',
            'import:started' => 'disableImport',
            'import:finished' => 'enableImport',
        ];
    }
}
