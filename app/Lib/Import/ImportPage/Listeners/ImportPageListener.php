<?php

namespace App\Lib\Import\ImportPage\Listeners;

use App\Models\ImportHistory;
use Http\Discovery\Exception\NotFoundException;

class ImportPageListener
{
    protected ?ImportHistory $import;

    public function __construct(
        protected string $system
    ) {
        $this->import = $this->getLastImport($this->system);
    }

    public static function createFor(string $system): self
    {
        return match ($system) {
            'BookingDotCom' => (new BdcImportPageListener($system)),
            'BookHostels' => (new HwImportPageListener($system)),
            default => (new static($system)),
        };
    }

    public function importStarted(array $options): void
    {
        $this->import->options['totalPages'] = $options['totalPages'];

        $this->import->save();
    }

    public function importInserted(bool $isInserted): void
    {
        $this->import->increment('checked');
        if ($isInserted === true) {
            $this->import->increment('inserted');
        }
    }

    public function importPageAdded(int $page): void
    {
        $this->import->options['lastPage'] = $page;

        $this->import->save();
    }

    public function importFinished(array $options): void
    {
        $this->import->update(['finished_at' => now()]);
    }

    protected function getLastImport($system): ?ImportHistory
    {
        return ImportHistory::lastBySystem($system)
                            ->first();
    }
}
