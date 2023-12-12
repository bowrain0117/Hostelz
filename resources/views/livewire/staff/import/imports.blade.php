<div class="row">

    <livewire:staff.import.book-hostels-import />

    <livewire:staff.import.bdc-import />

    <livewire:staff.import.hostelsclub-import />

    <hr>

    <div class="col-md-12 mt-5 mb-3">
        <p><strong>checked Imports:</strong> {{ $this->checkedImported }}</p>
        <p><strong>inserteed Imports:</strong> {{ $this->insertNewImported }}</p>

        <p>{{ now() }}</p>
    </div>

    <div class="col-md-12 mt-5 mb-3">
        <button class="btn btn-default" wire:click="insertNewListings">
            Insert new Listings ({{ $this->newListingsCount }})
        </button>
        <button class="btn btn-warning" wire:click="afterImportMaintenance">After Import Maintenance</button>

        <li><a href="{!! routeURL('staff-importedNameChanges') !!}">Name Changes</a></li>
        <li><a href="{!! routeURL('staff-dataChecks') !!}">Data Checks</a></li>
    </div>
</div>
