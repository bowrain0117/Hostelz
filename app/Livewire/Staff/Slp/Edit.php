<?php

namespace App\Livewire\Staff\Slp;

use App\Enums\CategorySlp;
use App\Enums\StatusSlp;
use App\Helpers\EventLog;
use App\Lib\Slp\Categories\EditAutoFillFields\EditAutoFillFields;
use App\Models\CityInfo;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Edit extends Component
{
    use WithFileUploads;

    public SpecialLandingPage $slp;

    public $tempContentPics;

    public $contentPics;

    public $tempMainPic;

    public $mainPic;

    public $city;

    public $isEditSlug = false;

    public Collection $faqs;

    public function mount(SpecialLandingPage $slp)
    {
        $this->slp = $slp;
        $this->mainPic = $this->slp->getFirstMedia('mainPic');
        $this->contentPics = $this->slp->getMedia('content');
        $this->city = $this->slp->subjectable?->id;
        $this->faqs = collect($this->slp->faqs);
    }

    public function render()
    {
        $picsForEditor = $this->getPicsForEditor();

        return view(
            'slp.livewire.edit',
            compact('picsForEditor')
        );
    }

    // content pics
    public function updatedTempContentPics()
    {
        $this->validate([
            'tempContentPics.*' => [
                File::image()->max(1 * 1024),
                Rule::dimensions()->maxWidth(800)->maxHeight(533),
            ],
        ]);
    }

    public function saveContentPics()
    {
        $this->validate([
            'tempContentPics.*' => [
                File::image()->max(1 * 1024),
                Rule::dimensions()->maxWidth(800)->maxHeight(533),
            ],
        ]);

        collect($this->tempContentPics)->map(function ($pic) {
            return $this->slp
                ->addMedia($pic->getRealPath())
                ->usingName($pic->getClientOriginalName())
                ->usingFileName($pic->getClientOriginalName())
                ->toMediaCollection('content');
        });

        $this->tempContentPics = null;

        $this->dispatch('imagesAdded', $this->getPicsForEditor());

        $this->contentPics = $this->getCurrentSlp()?->getMedia('content');
    }

    public function deleteContentPic(Media $pic)
    {
        $pic->delete();

        $this->dispatch('imagesAdded', $this->getPicsForEditor());

        $this->contentPics = $this->getCurrentSlp()?->getMedia('content');
    }

    // main pic
    public function updatedTempMainPic()
    {
        $this->validate([
            'tempMainPic' => [
                File::image()->max(1 * 1024),
                Rule::dimensions()->maxWidth(1000)->maxHeight(665),
            ],
        ]);
    }

    public function saveMainPic()
    {
        $this->validate([
            'tempMainPic' => [
                File::image()->max(1 * 1024),
                Rule::dimensions()->maxWidth(1000)->maxHeight(665),
            ],
        ]);

        $this->slp
            ->addMedia($this->tempMainPic->getRealPath())
            ->usingName($this->tempMainPic->getClientOriginalName())
            ->usingFileName($this->tempMainPic->getClientOriginalName())
            ->toMediaCollection('mainPic');

        $this->tempMainPic = null;

        $this->mainPic = $this->getCurrentSlp()?->getFirstMedia('mainPic');
    }

    public function deleteMainPic(Media $pic)
    {
        $pic->delete();

        $this->mainPic = $this->getCurrentSlp()?->getFirstMedia('mainPic');
    }

    public function updated($fieldName)
    {
        if (in_array($fieldName, ['slp.category', 'city', 'slp.slug'])) {
            $this->getValidator()->validate(
                Arr::where(
                    $this->rules(),
                    fn ($value, $key) => in_array($key, ['slp.category', 'city', 'slp.slug'])
                )
            );
        } else {
            $this->getValidator()->validateOnly($fieldName);
        }

        $this->autoFillFields($fieldName);
    }

    // Property

    public function getSelectsOptionsProperty()
    {
        return [
            'status' => StatusSlp::values(),
            'category' => CategorySlp::values(),
            'language' => Languages::allLiveSiteCodes(),
            'users' => $this->getUsers(),
            'cities' => CityInfo::get(['id', 'city', 'country']),
        ];
    }

    public function getTotalHostelsCountProperty()
    {
        $cityId = $this->city;

        if (! $cityId) {
            return null;
        }

        return Listing::query()
            ->areLive()
            ->byCityInfo(CityInfo::find($cityId))
            ->count();
    }

    public function getBestHostelsCountProperty()
    {
        if (! $this->city) {
            return null;
        }

        return Listing::query()
            ->hostelsForCategory(CategorySlp::Best, CityInfo::find($this->city))
            ->count();
    }

    public function getPartyHostelsCountProperty()
    {
        if (! $this->city) {
            return null;
        }

        return Listing::query()
            ->hostelsForCategory(CategorySlp::Party, CityInfo::find($this->city))
            ->count();
    }

    public function getPrivateHostelsCountProperty()
    {
        if (! $this->city) {
            return null;
        }

        return Listing::query()
            ->hostelsForCategory(CategorySlp::Private, CityInfo::find($this->city))
            ->count();
    }

    public function getCheapHostelsCountProperty()
    {
        if (! $this->city) {
            return null;
        }

        return Listing::query()
            ->hostelsForCategory(CategorySlp::Cheap, CityInfo::find($this->city))
            ->get()
            ->count();
    }

    public function save()
    {
        $this->getValidator()->validate();

        $subject = CityInfo::find($this->city);

        if (! $this->slp->slug) {
            $this->slp->slug = str($subject->city)->slug()->value();
        }

        $this->slp->subjectable()->associate($subject);

        $this->slp->save();

        $this->storeFaqs($this->slp, $this->faqs);

        EventLog::log(
            category: 'staff',
            action: 'save',
            subjectType: 'SLP',
            subjectID: $this->slp->id,
            subjectString: $this->slp->meta->title,
            data: json_encode($this->slp)
        );

        return redirect($this->slp->pathEdit)
            ->with('message', 'Slp successfully updated.');
    }

    protected function rules(): array
    {
        return [
            'slp.user_id' => ['required', 'exists:users,id'],
            'slp.status' => ['required', 'string'],
            'slp.language' => ['required', 'string', 'max:6'],
            'slp.number_featured_hostels' => ['required', 'integer', 'between:3,17'],

            'slp.title' => ['required', 'string', 'min:2'],
            'slp.meta_title' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $maxCharacters = 60;
                    $stringLength = strlen($this->slp->replaceShortcodes($value));

                    if ($stringLength > $maxCharacters) {
                        $fail('The "Meta Title" length is greater than ' . $maxCharacters . ' (' . $stringLength . ').');
                    }
                },
            ],
            'slp.meta_description' => ['required', 'string', 'max:160'],

            'slp.created_at' => ['required', 'date'],
            'slp.updated_at' => ['required', 'date'],
            'slp.slug' => [
                'string',
                'max:255',
            ],

            'slp.category' => ['required'],
            'slp.content' => ['required', 'string'],
            'slp.notes' => ['string'],

            'city' => ['required'],

            'faqs.*.question' => ['required', 'string'],
            'faqs.*.answer' => ['required', 'string'],
        ];
    }

    protected $messages = [
        'faqs.*.question' => 'The Question cannot be empty.',
        'faqs.*.answer' => 'The Answer cannot be empty.',
    ];

    // slug validation

    public function updatedSlpCategory()
    {
        if (! $this->city) {
            return;
        }

        $subject = CityInfo::find($this->city);

        if (! $this->slp->slug) {
            $this->slp->slug = str($subject->city)->slug()->value();
        }
    }

    public function updatedCity($value)
    {
        $subject = CityInfo::find($value);
        if (! $subject) {
            $this->city = null;

            return;
        }

        if (! $this->slp->category) {
            return;
        }

        if (! $this->slp->slug) {
            $this->slp->slug = str($subject->city)->slug()->value();
        }
    }

    private function getValidator(): self
    {
        return $this->withValidator(function (Validator $validator) {
            $validator->after(function ($validator) {
                $this->checkCity($validator);
                $this->checkSlug($validator);
            });
        });
    }

    private function checkCity(Validator $validator): void
    {
        $data = $validator->getData();

        $existedSlps = SpecialLandingPage::query()
            ->where([
                ['category', $data['slp']['category']],
                ['id', '!=', $this->slp->id],
            ])
            ->forCity($data['city'])
            ->get();

        if ($existedSlps->isNotEmpty()) {
            $city = CityInfo::find($data['city']);

            $slpsMessage = $existedSlps->map(function (SpecialLandingPage $slp) {
                return "<a href='{$slp->pathEdit}' target='_blank'>{$slp->meta->title} ({$slp->subjectable->city}, {$slp->subjectable->country})</a>";
            })->implode(', ');

            $validator->errors()->add(
                'city',
                "SLP already exists for this City <b>({$city?->city}, {$city?->country})</b> and Category <b>{$data['slp']['category']}</b>. <br/>{$slpsMessage}"
            );
        }
    }

    private function checkSlug(Validator $validator): void
    {
        $this->isEditSlug = false;

        if ($validator->errors()->has('city')) {
            return;
        }

        $data = $validator->getData();

        $similarSlug = SpecialLandingPage::query()
            ->where([
                ['category', $data['slp']['category']],
                ['slug', $this->slp->slug],
            ])
            ->when($this->slp->id, fn ($query) => $query->where('id', '!=', $this->slp->id))
            ->get();

        if ($similarSlug->isNotEmpty()) {
            $this->isEditSlug = ! $validator->errors()->has('city');

            $slpsMessage = $similarSlug->map(function (SpecialLandingPage $slp) {
                return "<a href='{$slp->pathEdit}' target='_blank'>{$slp->meta->title} ({$slp->subjectable->city}, {$slp->subjectable->country})</a>";
            })->implode(', ');
            $city = CityInfo::find($data['city']);
            $cityName = strtolower($city?->country);

            $validator->errors()->add(
                'slp.slug',
                "This slug already exists. You can now manually set a unique slug. Ideas: add the country name <b>{$cityName}</b> to the end.<br/>{$slpsMessage}"
            );
        }
    }

    // other

    private function autoFillFields($fieldName): void
    {
        if (
            in_array($fieldName, ['slp.user_id', 'slp.category', 'slp.number_featured_hostels', 'city'])
            && $this->city
            && ($city = CityInfo::find($this->city))
            && $this->slp->user_id
            && $this->slp->category
            && $this->slp->number_featured_hostels
        ) {
            $autoFill = EditAutoFillFields::create(
                $this->slp->number_featured_hostels,
                $city,
                $this->slp->category,
            );

            $this->slp->title = $this->slp->title ?: $autoFill->getTitle();
            $this->slp->meta_title = $this->slp->meta_title ?: $autoFill->getMetaTatile();
            $this->slp->meta_description = $this->slp->meta_description ?: $autoFill->getMetaDescription();

            if (blank($this->slp->content)) {
                $this->slp->content = $autoFill->getContent();
                $this->dispatch('contentUpdated', $this->slp->content);
            }
        }
    }

    private function getPicsForEditor()
    {
        return $this->getCurrentSlp()
            ?->getMedia('content')
            ->map(function ($pic) {
                return ['title' => $pic->name, 'value' => $pic->getUrl()];
            });
    }

    private function getUsers()
    {
        return User::where([
            ['access', 'like', '%admin%'],
        ])
            ->get();
    }

    private function getCurrentSlp()
    {
        return SpecialLandingPage::find($this->slp?->id);
    }

    // faq
    protected function storeFaqs($parent, $faqs): void
    {
        $faqs->each(fn ($faq) => filled($faq['id'])
            ? $parent->faqs()->whereId($faq['id'])->first()?->update($faq->only(['question', 'answer']))
            : $parent->faqs()->create($faq)
        );
    }

    public function addFaq()
    {
        $this->faqs->add([
            'id' => null,
            'question' => '',
            'answer' => '',
        ]);
    }

    public function removeFaq($index)
    {
        $faq = $this->faqs->pull($index);

        $this->slp->faqs()->whereId($faq['id'])?->delete();

        session()->flash('message', 'faq removed.');
    }
}
