<div class="row">

    <div class="col-md-10 mb-4">

        <div wire:loading class="slp-edite-spinner">
            <x-spinner style="width: 10rem; height: 10rem;" isActive="true"/>
        </div>

        <h2>District "{{ $district?->name ?? '---' }}"</h2>

        <form wire:submit="save">

            <x-input.group label="City *" for="cityId" :error="$errors->first('district.cityId')">
                <x-input.select-search wire:model.live="district.cityId" id="cityId">
                    <option value="">---</option>
                    @foreach($this->selectsOptions['cities'] as $item)
                        <option value="{{$item->id}}" @selected($item->id === $district->cityId)>
                            {{ $item->city }} ({{ $item->country }})
                        </option>
                    @endforeach
                </x-input.select-search>
            </x-input.group>

            <div class="form-group row">
                <div class="col-sm-offset-3 col-sm-9">
                    <div class="checkbox">
                        <label for="is_city_centre">
                            <input id="is_city_centre" type="checkbox" wire:model.live="district.is_city_centre">
                            Use
                            Special City Centre Page
                        </label>
                    </div>
                </div>
            </div>

            <x-input.group label="Name *" for="name" :error="$errors->first('district.name')">
                <input id="name" type="text" wire:model.blur="district.name" class="form-control" required
                        @disabled($this->district->is_city_centre)
                >
            </x-input.group>

            <x-input.group label="Type *" for="status" :error="$errors->first('district.type')">
                <select required class="form-control" id="status" wire:model.live="district.type"
                        @disabled($this->district->is_city_centre)
                >
                    @foreach($this->selectsOptions['types'] as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <hr>

            <div @class([
                'form-group row',
                'hide' => $this->district->is_city_centre,
            ])>
                <label for="mapLocation" class="col-sm-3 col-form-label">Map *</label>
                <div class="col-sm-9">
                    <p class="pb-5">
                        Click or Drag&Drop the marker to your exact location on the map. Zoom in to place the marker as
                        precisely as possible. You can also use the search field.
                    </p>

                    <div>
                        <div class="form-group">
                            <label for="mapSearchInput"
                                   class="col-sm-4 control-label font-weight-600">@langGet('ListingEditHandler.mapLocation.findAddress')</label>
                            <div class="col-sm-6 pb-5">
                                <input class="form-control" id="mapSearchInput"
                                       placeholder="@langGet('ListingEditHandler.mapLocation.findAddress')"
                                        @disabled($this->district->is_city_centre)
                                >
                            </div>
                            <button class="btn btn-primary" type="button"
                                    id="mapSearchButton">@langGet('ListingEditHandler.mapLocation.FindOnMap')</button>
                        </div>
                    </div>

                    <div wire:ignore id="mapCanvas" style="height:500px;"></div>

                    <button class="btn btn-link p-0 m-0" type="button"
                            id="mapSearchReset">@langGet('ListingEditHandler.mapLocation.cancelAndReset')</button>

                    @error('map')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <div class="form-group row">
                <label for="name" class="col-sm-3 col-form-label">longitude</label>
                <div class="col-sm-9">
                    <input id="longitude" disabled type="text" wire:model="district.longitude"
                           class="form-control">

                    @error('district.longitude')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <div class="form-group row">
                <label for="latitude" class="col-sm-3 col-form-label">latitude</label>
                <div class="col-sm-9">
                    <input id="latitude" disabled type="text" wire:model="district.latitude"
                           class="form-control">

                    @error('district.latitude')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <hr>

            <div class="form-group row">
                <label for="description" class="col-sm-3 col-form-label">Description *</label>
                <div class="col-sm-9">
                    <div wire:ignore>
                        <textarea class="form-control" id="description" wire:model.blur="district.description"
                                  rows="15"></textarea>
                    </div>

                    @error('district.description')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-offset-3 col-sm-9">
                    <div class="checkbox">
                        <label for="is_active">
                            <input id="is_active" type="checkbox" wire:model="district.is_active"> Is Active
                        </label>
                    </div>

                    @error('district.is_active')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <h3>Unique FAQ</h3>

            @foreach($this->faqs as $index => $faq)
                <div class="row">
                    <div class="col-md-10">
                        <input id="faqs_{{$index}}_id" type="hidden" wire:model="faqs.{{$index}}.id"
                               class="form-control">

                        <div class="form-group row">
                            <label for="faqs_{{$index}}_question" class="col-sm-4 col-form-label">Questions</label>
                            <div class="col-sm-8">
                                <textarea class="form-control"
                                          id="faqs_{{$index}}_question"
                                          wire:model="faqs.{{$index}}.question" rows="2"></textarea>

                                <p><em>Word Count: [{{ str($faq['question'])->wordCount() }}] (max. 10 words
                                        recommended)</em></p>

                                @error("faqs.{$index}.question")
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="faqs_{{$index}}_answer" class="col-sm-4 col-form-label">Answer</label>
                            <div class="col-sm-8">
                                <textarea class="form-control"
                                          id="faqs_{{$index}}_answer"
                                          wire:model="faqs.{{$index}}.answer" rows="3"></textarea>

                                <p><em>Word Count: [{{ str($faq['answer'])->wordCount() }}] (max. 160-200 words
                                        recommended)</em></p>

                                @error("faqs.{$index}.answer")
                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button"
                                onclick="confirm('Are you sure you want to remove the faq?') || event.stopImmediatePropagation()"
                                wire:click="removeFaq({{$index}})"
                                class="btn btn-danger"
                        >Remove
                        </button>
                    </div>
                </div>

                <hr>
            @endforeach

            <div class="row text-right">
                <div class="col"></div>
                <button type="button" wire:click="addFaq" class="btn btn-warning my-3">Add New FAQ</button>
            </div>

            <div>
                @if (session()->has('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                @endif
            </div>

            <button type="submit" class="btn btn-success">Save</button>

            @if($errors->any())
                <div class="my-3">
                    {!! implode('', $errors->all('<p class="text-danger small mt-1">:message</p>')) !!}
                </div>
            @endif

        </form>
    </div>

    <div class="col-md-2">
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>

            @if($district->city)
                <a href="{{ $district->city->pathEdit }}"
                   class="list-group-item" target="_blank">
                    <span class="pull-right">&raquo;</span> City
                </a>
            @endif

            @if($district->is_active)
                <a href="{{ $district->path }}"
                   class="list-group-item" target="_blank">
                    <span class="pull-right">&raquo;</span> Go to Page
                </a>
            @endif
        </div>
    </div>

    @if(filled($district->neighborhoods))
        <div class="col-md-2">
            <div class="list-group">
                <a href="#" class="list-group-item active">Districts</a>

                @foreach($district->neighborhoods as $nerghborhood)
                    <a href="{{ $nerghborhood->pathEdit }}"
                       class="list-group-item" target="_blank">
                        <span class="pull-right">&raquo;</span> {{ $nerghborhood->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif


    @pushOnce('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={!! urlencode(config('custom.googleApiKey.clientSide')) !!}"></script>
    <script src="https://cdn.tiny.cloud/1/p50y1gggq6kwvzuew7dcy8qbma491dt85icr2zsnoyyhu1rm/tinymce/6/tinymce.min.js"></script>
    @endPushOnce

    @push('scripts')

        <script type="text/javascript">
            document.addEventListener('livewire:init', () => {
                const listingPoint = new google.maps.LatLng({
                    lat: parseFloat(@js($mapCenter['latitude'])),
                    lng: parseFloat(@js($mapCenter['longitude']))
                });

                const map = new google.maps.Map(document.getElementById("mapCanvas"), {
                    zoom: 12,
                    center: listingPoint,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    scaleControl: true,
                    streetViewControl: false,
                    minZoom: 3,
                    maxZoom: 16,
                });

                const marker = new google.maps.Marker({
                    position: listingPoint,
                    map: map,
                    draggable: true
                });

                {{-- Move the listingPoint to the center if it isn't visible (have to use an event because the map's bounds aren't immediately known at first) --}}
                {{-- (it may not be visible if the geocoding changed, or just if the user saved the map panned away from the marker) --}}
                let didBoundsCheck = false;
                google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
                    if (!didBoundsCheck) {
                        didBoundsCheck = true;
                        if (!map.getBounds().contains(listingPoint)) map.setCenter(listingPoint);
                    }
                });

                {{-- Allow clicking to place the marker. --}}
                google.maps.event.addListener(map, "click", function (event) {
                    setLocation(event.latLng)
                });

                google.maps.event.addListener(marker, 'dragend', function (event) {
                    setLocation(event.latLng)
                });

                searchOnMap()

                function searchOnMap() {
                    const mapSearchButton = document.getElementById("mapSearchButton");
                    const mapSearchInput = document.getElementById("mapSearchInput");

                    mapSearchInput.addEventListener("keypress", function (event) {
                        if (event.key === "Enter") {
                            event.preventDefault();

                            mapSearchButton.click();
                        }
                    });

                    mapSearchButton.addEventListener("click", (event) => {
                        event.preventDefault();

                        const address = mapSearchInput.value;
                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({'address': address}, function (results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                map.setCenter(results[0].geometry.location);
                                map.setZoom(14);

                                setLocation(results[0].geometry.location)
                            } else {
                                alert("{{{ langGet('ListingEditHandler.mapLocation.CantGeocode') }}}");
                            }
                        });
                    });

                    document.getElementById("mapSearchReset").addEventListener("click", (event) => {
                        event.preventDefault();

                        const position = {
                            lat: parseFloat(@js($district->latitude)),
                            lng: parseFloat(@js($district->longitude))
                        }

                        map.setCenter(position);
                        map.setZoom(14);

                        marker.setPosition({
                            lat: parseFloat(@js($district->latitude)),
                            lng: parseFloat(@js($district->longitude))
                        });
                    });
                }

                function setLocation(location) {
                    marker.setPosition(location);

                    @this.
                    set('district.latitude', location.lat())
                    @this.set('district.longitude', location.lng())
                }
            });

            document.addEventListener('livewire:init', function () {
                const editorId = '#description';

                const initOptions = {
                    selector: editorId,
                    forced_root_block: false,
                    plugins: 'image code link lists',

                    toolbar1: "undo redo | bullist numlist | link | removeformat code | bold italic | paste pastetext",
                    toolbar2: "blocks | alignleft aligncenter alignright alignjustify",

                    setup: function (editor) {
                        editor.on('init change', function () {
                            editor.save();
                        });
                        editor.on('change', function (e) {
                            @this.
                            set('district.description', editor.getContent())
                        });
                    },
                    content_style: 'img { max-width: 400px; height: auto; }'
                };

                tinymce.init(initOptions);
            })
        </script>

        <style type="text/css">
            .choices__list.choices__list--dropdown {
                z-index: 1000;
            }
        </style>
    @endpush
</div>