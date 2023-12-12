<?php

namespace App\Lib\Import\ImportPage\Listeners;

class HwImportPageListener extends ImportPageListener
{
    public function importStarted(array $options): void
    {
        $this->import->options['batchId'] = $options['batchId'];

        parent::importStarted($options);
    }
}
