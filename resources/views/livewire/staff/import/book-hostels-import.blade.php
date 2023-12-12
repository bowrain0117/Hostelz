<div class="my-3">
    <div class="col-md-12 mb-3">
        <h2>HostelWorld</h2>
        <button class="btn btn-default"
                wire:click="resetImport"
                @disabled(!$isActive)
        >resetImport
        </button>

        <button class="btn btn-warning "
                wire:click="startImport({{true}})"
                @disabled($isActive)
                wire:loading.attr="disabled"
        >Import HostelWorld TEST!
        </button>

        <button class="btn btn-primary"
                wire:click="startImport"
                @disabled($isActive)
                wire:loading.attr="disabled"
        >Import HostelWorld
        </button>
    </div>

    <div class="col-md-12" wire:loading wire:target="startImport">
        <div class="alert alert-info text-center" role="alert">Adding items to queue</div>
    </div>

    <x-spinner :isActive="$isActive"/>

    <div class="col-md-12 mb-3">
        <x-badge title="current page" :count="$current"/>

        <x-badge title="total pages" :count="$maxCount"/>

        @if($import)
            <x-badge title="checked hostels" :count="$import->checked" color="warning"/>

            <x-badge title="new hostels" :count="$import->inserted" color="warning"/>

            <p>isActive: {{ $isActive ? 'active' : 'inActive' }}</p>

            <p>last import started at: {{ $import->started_at }}</p>
            <p>last import finished at: {{ $import->finished_at }}</p>
            <p>last import cancelled at: {{ $import->cancelled_at }}</p>
        @endif

        <code>test: {{ now() }}</code>

    </div>

    <div class="col-md-12">
        <x-progress-bar :percentage="$percentage" class="progress-bar progress-bar-success"/>
    </div>

    @if($imports)
        <div class="col-md-12 mb-3">
            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#importsHw"
                    aria-expanded="false" aria-controls="importsHw">
                Last 20 imports
            </button>
            <div class="collapse" id="importsHw">
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
