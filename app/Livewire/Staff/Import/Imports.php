<?php

namespace App\Livewire\Staff\Import;

use App\Console\Commands\CreateListingsFromImporteds;
use App\Console\Commands\WebsiteMaintenance;
use App\Models\Imported;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Imports extends Component
{
    public function render()
    {
        return view('livewire.staff.import.imports');
    }

    public function insertNewListings(): void
    {
        Artisan::call(CreateListingsFromImporteds::class);
    }

    public function afterImportMaintenance(): void
    {
        Artisan::call(WebsiteMaintenance::class, ['period' => 'afterListingDataImport']);
    }

    public function resetImport()
    {
        cache()->forget('checkedImported');
        cache()->forget('insertNewImported');
    }

    public function getCheckedImportedProperty(): int
    {
        return cache('checkedImported') ?? 0;
    }

    public function getInsertNewImportedProperty(): int
    {
        return cache('insertNewImported') ?? 0;
    }

    public function getNewListingsCountProperty(): int
    {
        return Imported::where([
            ['hostelID', 0],
            ['country', '!=', ''],
            ['propertyType', '!=', ''],
            ['status', 'active'],
        ])->count();
    }

    public function getListeners(): array
    {
        return [
            'echo-private:import-page-added.BookHostels,.import.page.added' => '$refresh',
            'echo-private:import-page-added.BookingDotCom,.import.page.added' => '$refresh',
            'echo-private:import-inserted,.import.inserted' => '$refresh',
            'echo-private:import-update,.import.update' => '$refresh',
            'resetImport',
            'startedImport' => '$refresh',
        ];
    }
}
