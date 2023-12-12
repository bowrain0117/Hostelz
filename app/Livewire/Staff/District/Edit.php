<?php

namespace App\Livewire\Staff\District;

use App\Enums\District\Type;
use App\Helpers\EventLog;
use App\Models\CityInfo;
use App\Models\District;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use Livewire\Component;

class Edit extends Component
{
    public District $district;

    public $mapCenter;

    public Collection $faqs;

    public function mount(District $district)
    {
        $this->district = $district;

        if ($this->district->latitude && $this->district->longitude) {
            $this->mapCenter = [
                'latitude' => $this->district->latitude,
                'longitude' => $this->district->longitude,
            ];
        } elseif ($this->district->city) {
            $this->mapCenter = [
                'latitude' => $this->district->city->latitude,
                'longitude' => $this->district->city->longitude,
            ];
        } else {
            $this->mapCenter = [
                'latitude' => -34.397,
                'longitude' => 150.644,
            ];
        }

        $this->faqs = collect($this->district->faqs);
    }

    public function render()
    {
        return view('districts.livewire.edit');
    }

    public function updated($fieldName)
    {
        $parredFields = ['district.name', 'district.type', 'district.is_city_centre'/*, 'district.cityId'*/];
        if (in_array($fieldName, $parredFields, true)) {
            $this->getValidator()->validate(
                Arr::where(
                    $this->rules(),
                    fn ($value, $key) => in_array($key, $parredFields, true)
                )
            );
        } else {
            $this->getValidator()->validateOnly($fieldName);
        }
    }

    public function updatingDistrictIsCityCentre($checked)
    {
        if (! $checked) {
            return;
        }

        if (! $this->district?->city) {
            return;
        }

        $this->district->name = 'City Centre';
        $this->district->longitude = $this->district->city->longitude;
        $this->district->latitude = $this->district->city->latitude;
        $this->district->type = Type::In;
    }

    public function getSelectsOptionsProperty()
    {
        return [
            'types' => Type::getValues(),
            'cities' => CityInfo::query()->get(['id', 'city', 'country']),
        ];
    }

    protected function rules(): array
    {
        return [
            'district.cityId' => ['required', 'exists:cityInfo,id'],
            'district.name' => ['required', 'string', 'min:2'],
            'district.type' => ['required', 'string', new Enum(Type::class)],
            'district.latitude' => ['required', 'between:-90,90'],
            'district.longitude' => ['required', 'between:-180,180'],
            'district.description' => ['required', 'string', 'min:2'],
            'district.is_active' => ['required', 'boolean'],
            'district.is_city_centre' => ['boolean'],
            'faqs.*.question' => ['required', 'string'],
            'faqs.*.answer' => ['required', 'string'],
        ];
    }

    protected $messages = [
        'faqs.*.question' => 'The Question cannot be empty.',
        'faqs.*.answer' => 'The Answer cannot be empty.',
    ];

    public function save()
    {
        $this->getValidator()->validate();

        $this->district->save();

        $this->storeFaqs($this->district, $this->faqs);

        $this->district->refresh();

        $this->faqs = collect($this->district->faqs);

        EventLog::log(
            category: 'staff',
            action: 'edit',
            subjectType: 'District',
            subjectID: $this->district->id,
            subjectString: $this->district->name,
            data: json_encode($this->district)
        );

        return redirect($this->district->pathEdit)
            ->with('message', 'District successfully updated.');
    }

    // faq
    protected function storeFaqs($parent, $faqs): void
    {
        $faqs->each(fn ($faq) => filled($faq['id'])
            ? $parent->faqs()->whereId($faq['id'])->first()?->update($faq)
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

        $this->district->faqs()->whereId($faq['id'])?->delete();

        session()->flash('message', 'faq removed.');
    }

    private function getValidator(): self
    {
        return $this->withValidator(function (Validator $validator) {
            $validator->after(function ($validator) {
                $similar = District::query()
                    ->when($this->district?->id, fn ($q) => $q->where('id', '!=', $this->district->id))
                    ->where([
                        ['cityId', $this->district?->cityId],
                        ['name', $this->district->name],
                        ['type', $this->district->type],
                    ])
                    ->get();

                if ($similar->isNotEmpty()) {
                    $message = $similar
                        ->map(fn (District $district) => "<a href='{$district->pathEdit}' target='_blank'>{$district->name} ({$district->city->city}, {$district->city->country})</a>")
                        ->join(',');
                    $validator->errors()->add(
                        'district.name',
                        "The name ({$this->district->name}) already exists with type ({$this->district->type->value}).<br/>{$message}"
                    );
                }
            });
        });
    }
}
