<div class="container">
    @include('staff.checkAvailability.form')

    <div wire:loading class="m-5">
        <div class="spinner-border text-danger" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <div wire:loading.remove>
        @include('staff.checkAvailability.full')
        @include('staff.checkAvailability.hostelworld')
        @include('staff.checkAvailability.booking')
        @include('staff.checkAvailability.rawData')
    </div>
</div>
