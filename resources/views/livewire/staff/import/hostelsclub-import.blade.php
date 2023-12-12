<div class="my-5">
    <div class="col-md-12 mb-3">
        <h2>HostelsClub</h2>

        <button class="btn btn-default"
                wire:click="resetImport"
                @disabled(!$isActive)
        >
            resetImport
        </button>

        <button class="btn btn-warning "
                wire:click="startImport({{true}})"
                @disabled($isActive)
                wire:loading.attr="disabled"
        >Import HostelsClub TEST!
        </button>

        <button class="btn btn-primary"
                wire:click="startImport"
                @disabled($isActive)
                wire:loading.attr="disabled"
        >Import HostelsClub
        </button>
    </div>

    <div class="col-md-12" wire:loading wire:target="startImport">
        <div class="alert alert-info text-center" role="alert">Adding items to queue</div>
    </div>

    <x-spinner :isActive="$isActive"/>

    <div class="col-md-12 mb-3">
        <x-badge title="current hostels" :count="$current"/>

        <x-badge title="total hostels" :count="$maxCount"/>

        <code>{{ now() }}</code>
    </div>

    <div class="col-md-12">
        <x-progress-bar :percentage="$percentage"/>
    </div>

    @if($imports->isNotEmpty())
        <div class="col-md-12 mb-3">
            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#importsBdc"
                    aria-expanded="false" aria-controls="importsBdc">
                Last 20 imports
            </button>
            <div class="collapse" id="importsBdc">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>System</th>
                            <th>Checked hostels</th>
                            <th>New hostels</th>
                            <th>started</th>
                            <th>finished</th>
                            <th>cancelled</th>
                            <th>options</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($imports as $_import)
                            <tr>
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td>{{ $_import->system }}</td>
                                <td>{{ $_import->checked }}</td>
                                <td>{{ $_import->inserted }}</td>
                                <td>{{ $_import->started_at }}</td>
                                <td>
                                    @if($_import->finished_at)
                                        {{ $_import->finished_at?->diffForHumans() }},
                                    @endif
                                    {{ $_import->finished_at }}
                                </td>
                                <td>{{ $_import->cancelled_at }}</td>
                                <td>
                                    @foreach($_import->options->collect() as $key => $option)
                                        <p><b>{{ $key }}:</b> {{ $option }}</p>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($import)
        <div class="col-md-12 mb-3">
            <div>import: @dump($import->toArray())</div>
        </div>
    @endif

</div>
