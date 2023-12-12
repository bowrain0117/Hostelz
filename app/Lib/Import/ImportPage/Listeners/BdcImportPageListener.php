<?php

namespace App\Lib\Import\ImportPage\Listeners;

class BdcImportPageListener extends ImportPageListener
{
    public function importPageAdded(int $page): void
    {
        $this->import->options['lastPage'] = $this->getAll() - $page;

        $this->import->save();
    }

    // todo
    private function getAll(): int
    {
        return $this->import?->getOption('totalPages') ?? 0;
    }
}
