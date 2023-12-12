@php use App\Models\Listing\Listing; @endphp
<div class="row">

    <div class="col-md-10 mb-4">

        <div wire:loading class="slp-edite-spinner">
            <x-spinner style="width: 10rem; height: 10rem;" isActive="true"/>
        </div>

        <h2>Special Landing Page for "{{ $slp->subjectable?->city ?? '---' }}"</h2>

        <form wire:submit="save">

            <x-input.group label="User *" for="user_id" :error="$errors->first('slp.user_id')">
                <select required class="form-control" id="user_id" wire:model.live="slp.user_id">
                    <option value="">---</option>
                    @foreach($this->selectsOptions['users'] as $item)
                        <option value="{{$item->id}}">{{ $item->username }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group label="Category *" for="category" :error="$errors->first('slp.category')">
                <select required class="form-control" id="status" wire:model.live="slp.category">
                    <option value="" disabled>select category</option>
                    @foreach($this->selectsOptions['category'] as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <x-input.group label="City *" for="city" :error="$errors->first('city')">
                <x-input.select-search wire:model.live="city" id="city" required>
                    <option value="">---</option>
                    @foreach($this->selectsOptions['cities'] as $item)
                        <option wire.key="{{$item->id}}" value="{{$item->id}}" @selected($item->id === $city)>
                            {{ $item->city }} ({{ $item->country }})
                        </option>
                    @endforeach
                </x-input.select-search>
            </x-input.group>

            <x-input.group label="Slug" for="slug" :error="$errors->first('slp.slug')">
                <input wire:model.live.blur="slp.slug" @readonly(!$isEditSlug) id="slug" type="text"
                       class="form-control">
            </x-input.group>

            <hr>

            <x-input.group :error="$errors->first('slp.number_featured_hostels')" label="Numbers of Hostels featured"
                           for="number_featured_hostels">
                <input wire:model.live.blur="slp.number_featured_hostels" id="number_featured_hostels" required
                       type="number"
                       min="3" max="17" class="form-control">
            </x-input.group>

            <hr>

            <x-input.group :error="$errors->first('slp.title')" label="Title *" for="title">
                <input id="title" type="text" wire:model.live.blur="slp.title" class="form-control" required>
            </x-input.group>

            <x-input.group :error="$errors->first('slp.meta_title')" label="Meta Title *" for="meta_title">
                <input id="meta_title" type="text" wire:model.live.blur="slp.meta_title" class="form-control">
            </x-input.group>

            <x-input.group :error="$errors->first('slp.meta_description')" label="Meta Description *"
                           for="meta_description">
                <textarea class="form-control" id="meta_description" wire:model.live.blur="slp.meta_description"
                          rows="3"></textarea>
            </x-input.group>

            <hr>

            <x-input.group label="Status" for="status" :error="$errors->first('slp.status')">
                <select required class="form-control" id="status" wire:model.live="slp.status">
                    @foreach($this->selectsOptions['status'] as $item)
                        <option value="{{$item}}">{{ $item }}</option>
                    @endforeach
                </select>
            </x-input.group>

            <hr>

            <div class="form-group row">
                <div class="col-sm-3">
                    <label for="tempMainPic" class="col-form-label">Main Pic</label>
                    <p class="small">(1000px x 665px)</p>
                </div>

                <div class="col-sm-9">
                    <div class="my-3">

                        <input type="file" id="tempMainPic" wire:model.live="tempMainPic"
                               accept=".jpg, .jpeg, .png, .webp">

                        @if ($tempMainPic)
                            <div>
                                <h5 class="text-center">Preview</h5>
                                <p class="text-center"><img src="{{ $tempMainPic->temporaryUrl() }}"
                                                            style="max-height: 200px"></p>
                                <p class="text-center bold">{{ formatFileSize($tempMainPic->getSize()) }}</p>
                            </div>
                        @endif

                        @error('tempMainPic')
                        <div class="text-danger small my-2">
                            {{ $message }}
                        </div>
                        @enderror

                        <button type="button" wire:click="saveMainPic" class="btn btn-success d-block mt-2">
                            Save Main Pic
                            <span wire:loading wire:target="tempMainPic, saveMainPic, deleteMainPic">
                                <x-spinner isActive="true"/>
                            </span>
                        </button>
                    </div>

                    @if($mainPic)
                        <div class="position-relative d-inline-block">
                            <button type="button" wire:click="deleteMainPic({{$mainPic->id}})"
                                    class="btn-danger btn btn-sm position-absolute top-0 end-0 "><i
                                        class="fa fa-trash-o"></i></button>
                            <img src="{{ $mainPic->getFullUrl('thumbnail') }}" alt="" style="max-width: 100px">
                        </div>
                    @endif

                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-3">
                    <label for="tempContentPics" class="col-form-label">Pics for content</label>
                    <p class="small">(800px x 533px)</p>
                </div>

                <div class="col-sm-9">
                    <div class="my-3">

                        <input type="file" id="tempContentPics" wire:model.live="tempContentPics" multiple
                               accept=".jpg, .jpeg, .png, .webp">

                        @if ($tempContentPics)
                            <h5>Previews</h5>
                            <div class="d-flex my-2">
                                @foreach($tempContentPics as $tempPic)
                                    <div style="width: 33%;">
                                        <p class="text-center"><img src="{{ $tempPic->temporaryUrl() }}"
                                                                    style="max-width: 100%"></p>
                                        <p class="text-center bold">{{ formatFileSize($tempPic->getSize()) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @error('tempContentPics')
                        <div class="text-danger small my-2">
                            {{ $message }}
                        </div>
                        @enderror

                        @error('tempContentPics.*')
                        <div class="text-danger small my-2">
                            {{ $message }}
                        </div>
                        @enderror

                        <button type="button" wire:click="saveContentPics" class="btn btn-success d-block my-2">
                            Save Photos
                            <span wire:loading wire:target="tempContentPics, saveContentPics, deleteContentPic">
                                <x-spinner isActive="true"/>
                            </span>
                        </button>
                    </div>

                    @foreach($contentPics as $pic)
                        <div class="position-relative d-inline-block">
                            <button type="button" wire:click="deleteContentPic({{$pic->id}})"
                                    class="btn-danger btn btn-sm position-absolute top-0 end-0 "><i
                                        class="fa fa-trash-o"></i></button>
                            <img src="{{ $pic->getFullUrl() }}" alt="" style="max-width: 100px">
                        </div>
                    @endforeach

                </div>
            </div>

            <hr>

            <x-input.group label="Content *" for="content" :error="$errors->first('slp.content')">
                <div wire:ignore>
                    <textarea class="form-control" id="content" wire:model.live="slp.content" rows="15"></textarea>
                </div>
            </x-input.group>

            <h3>Unique FAQ</h3>

            @foreach($this->faqs as $index => $faq)
                <div class="row">
                    <div class="col-md-10">
                        <input id="faqs_{{$index}}_id" type="hidden" wire:model.live.blur="faqs.{{$index}}.id"
                               class="form-control">

                        <div class="form-group row">
                            <label for="faqs_{{$index}}_question" class="col-sm-4 col-form-label">Questions</label>
                            <div class="col-sm-8">
                                <textarea class="form-control"
                                          id="faqs_{{$index}}_question"
                                          wire:model.live.blur="faqs.{{$index}}.question" rows="2"></textarea>

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
                                          wire:model.live.blur="faqs.{{$index}}.answer" rows="3"></textarea>

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

            <x-input.group :error="$errors->first('slp.notes')" label="Notes" for="notes">
                <textarea class="form-control" id="notes" wire:model.live="slp.notes" name="notes" rows="3"></textarea>
            </x-input.group>

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

        @pushOnce('scripts')
        <script type="text/javascript"
                src="https://cdn.tiny.cloud/1/p50y1gggq6kwvzuew7dcy8qbma491dt85icr2zsnoyyhu1rm/tinymce/6/tinymce.min.js"></script>
        @endPushOnce

        @push('scripts')
            <script>
                document.addEventListener('livewire:init', function () {
                    let editorId = '#content';
                    let imagesLists = @js($picsForEditor);

                    let initOptions = {
                        selector: editorId,
                        forced_root_block: false,
                        plugins: 'image code link lists',

                        toolbar1: "undo redo | blocks | removeformat code | bold italic | alignleft aligncenter alignright alignjustify",

                        toolbar2: "cheapShortcodes | bestShortcodes | privateShortcodes | patryShortcodes | genericShortcodes",

                        toolbar3: "image | link | bullist numlist",

                        image_title: true,
                        image_uploadtab: true,
                        automatic_uploads: true,
                        images_file_types: 'jpg,png,webp',
                        image_prepend_url: 'https://',

                        image_class_list: [
                            {title: 'None', value: ''},
                            {title: 'img-fluid', value: 'img-fluid'},
                        ],

                        image_list: imagesLists,

                        setup: function (editor) {
                            editor.on('init change', function () {
                                editor.save();
                            });
                            editor.on('change', function (e) {
                                @this.
                                set('slp.content', editor.getContent());
                            });

                            editor.ui.registry.addMenuButton('cheapShortcodes', {
                                text: 'Cheap',
                                fetch: function (callback) {
                                    var items = [
                                        {
                                            type: 'menuitem',
                                            text: 'Cheap Hostels List',
                                            onAction: function () {
                                                editor.insertContent('[slp:CheapHostelsList]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Cheap Hostels Card List',
                                            onAction: function () {
                                                editor.insertContent('[slp:CheapHostelsCard]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Compare Table',
                                            onAction: function () {
                                                editor.insertContent('[slp:CompareTable]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Average Price Graph',
                                            onAction: function () {
                                                editor.insertContent('[slp:AveragePriceGraph]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'When To Book',
                                            onAction: function () {
                                                editor.insertContent('[slp:WhenBook]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'FAQ',
                                            onAction: function () {
                                                editor.insertContent('[slp:FAQ]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Top Hostel',
                                            onAction: function () {
                                                editor.insertContent('[slp:TopHostel]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Slider',
                                            onAction: function () {
                                                editor.insertContent('[slp:SliderHostels]');
                                            }
                                        },
                                    ];
                                    callback(items);
                                }
                            });

                            editor.ui.registry.addMenuButton('bestShortcodes', {
                                text: 'Best',
                                fetch: function (callback) {
                                    var items = [
                                        {
                                            type: 'menuitem',
                                            text: 'Best Hostels list',
                                            onAction: function () {
                                                editor.insertContent('[slp:ListHostels]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Best Hostels Card List',
                                            onAction: function () {
                                                editor.insertContent('[slp:CardHostels]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Compare Table',
                                            onAction: function () {
                                                editor.insertContent('[slp:CompareTable]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Average Price Graph',
                                            onAction: function () {
                                                editor.insertContent('[slp:AveragePriceGraph]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'When To Book',
                                            onAction: function () {
                                                editor.insertContent('[slp:WhenBook]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'FAQ',
                                            onAction: function () {
                                                editor.insertContent('[slp:FAQ]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Top Hostel',
                                            onAction: function () {
                                                editor.insertContent('[slp:TopHostel]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Slider',
                                            onAction: function () {
                                                editor.insertContent('[slp:SliderHostels]');
                                            }
                                        },
                                    ];
                                    callback(items);
                                }
                            });

                            editor.ui.registry.addMenuButton('privateShortcodes', {
                                text: 'Private',
                                fetch: function (callback) {
                                    var items = [
                                        {
                                            type: 'menuitem',
                                            text: 'Private Hostels list',
                                            onAction: function () {
                                                editor.insertContent('[slp:ListHostels]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Private Hostels Card List',
                                            onAction: function () {
                                                editor.insertContent('[slp:PrivateHostels]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Compare Table',
                                            onAction: function () {
                                                editor.insertContent('[slp:CompareTable]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Average Price Graph',
                                            onAction: function () {
                                                editor.insertContent('[slp:AveragePriceGraph]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'When To Book',
                                            onAction: function () {
                                                editor.insertContent('[slp:WhenBook]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'FAQ',
                                            onAction: function () {
                                                editor.insertContent('[slp:FAQ]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Top Hostel',
                                            onAction: function () {
                                                editor.insertContent('[slp:TopHostel]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Slider',
                                            onAction: function () {
                                                editor.insertContent('[slp:SliderHostels]');
                                            }
                                        },
                                    ];
                                    callback(items);
                                }
                            });

                            editor.ui.registry.addMenuButton('patryShortcodes', {
                                text: 'Party',
                                fetch: function (callback) {
                                    var items = [
                                        {
                                            type: 'menuitem',
                                            text: 'Party Hostels list',
                                            onAction: function () {
                                                editor.insertContent('[slp:ListHostels]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Party Hostels Card List',
                                            onAction: function () {
                                                editor.insertContent('[slp:CardHostels]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Compare Table',
                                            onAction: function () {
                                                editor.insertContent('[slp:CompareTable]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Average Price Graph',
                                            onAction: function () {
                                                editor.insertContent('[slp:AveragePriceGraph]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'When To Book',
                                            onAction: function () {
                                                editor.insertContent('[slp:WhenBook]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'FAQ',
                                            onAction: function () {
                                                editor.insertContent('[slp:FAQ]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Top Hostel',
                                            onAction: function () {
                                                editor.insertContent('[slp:TopHostel]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Slider',
                                            onAction: function () {
                                                editor.insertContent('[slp:SliderHostels]');
                                            }
                                        },
                                    ];
                                    callback(items);
                                }
                            });

                            editor.ui.registry.addMenuButton('genericShortcodes', {
                                text: 'Generic Shortcodes',
                                fetch: function (callback) {
                                    var items = [
                                        {
                                            type: 'menuitem',
                                            text: 'Current Year',
                                            onAction: function () {
                                                editor.insertContent('[year]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Current Month',
                                            onAction: function () {
                                                editor.insertContent('[month]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'City Name',
                                            onAction: function () {
                                                editor.insertContent('[city]');
                                            }
                                        },
                                        {
                                            type: 'menuitem',
                                            text: 'Numbers of Hostels featured',
                                            onAction: function () {
                                                editor.insertContent('[number]');
                                            }
                                        },
                                    ];
                                    callback(items);
                                }
                            });
                        },
                        content_style: 'img { max-width: 400px; height: auto; }'
                    };

                    tinymce.init(initOptions);

                    document.addEventListener('imagesAdded', function (data) {

                        tinymce.remove(editorId);

                        initOptions.image_list = data.detail;

                        tinymce.init(initOptions);
                    })

                    document.addEventListener('contentUpdated', function (data) {
                        tinymce.activeEditor.setContent(data.detail);
                    })
                })
            </script>
        @endpush

    </div>

    <div class="col-md-2">
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>

            @if($slp->subjectable?->city)
                <a href="{{ $slp->subjectable->pathEdit }}"
                   class="list-group-item" target="_blank">
                    <span class="pull-right">&raquo;</span> City
                </a>
            @endif

            @if($slp->isPublished)
                <a href="{{ $slp->path }}"
                   class="list-group-item" target="_blank">
                    <span class="pull-right">&raquo;</span> Go to Page
                </a>
            @endif

            @if($slp->subjectable?->city && $slp->category)
                <a href="{{
                    routeURL('staff-listings', [
                        'search' => [
                            'city' => $slp->subjectable->city,
                            'propertyType' => 'Hostel',
                            'uniqeTextFor' => $slp->category->value,
                            'ids' => $slp->hostels->pluck('id')->join(','),
                            ],
                        'mode' => 'searchAndList',
                        'sort' => ['combinedRating' => 'desc']
                    ])
                }}"
                   class="list-group-item">
                    Unique Text for Hostels {{ $slp->uniqueTextListingsNumber }} / {{ $slp->number_featured_hostels }}
                </a>
            @endif
        </div>

        <div class="list-group">
            <a href="#" class="list-group-item active">City info</a>

            <a href="#" class="list-group-item">
                <b>Total Hostels: {{ $this->totalHostelsCount ?? '---' }}</b><br>
            </a>

            <a href="#" class="list-group-item">
                <b>Best Hostels: {{ $this->bestHostelsCount ?? '---' }}</b><br>
                <span class="small">min. rating {{ Listing::TOP_HOSTELS_MIN_RATIING / 10 }}<br>
                active price</span>
            </a>

            <a href="#" class="list-group-item">
                <b>Private Rooms: {{ $this->privateHostelsCount ?? '---' }}</b><br>
                <span class="small">active price</span>
            </a>

            <a href="#" class="list-group-item">
                <b>Party: {{ $this->partyHostelsCount ?? '---' }}</b><br>
                <span class="small">min. active on Hostelworld</span>
                <span class="small">must be labelled for “party”</span>
                <span class="small">active price</span>
            </a>

            <a href="#" class="list-group-item">
                <b>Cheapest: {{ $this->cheapHostelsCount ?? '---' }}</b><br>
                <span class="small">active price</span>
            </a>
        </div>

    </div>
</div>
