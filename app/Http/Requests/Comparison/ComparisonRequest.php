<?php

namespace App\Http\Requests\Comparison;

use App\Models\Listing\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComparisonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'listingsId' => explode('+', $this->listingsId),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'listingsId' => [
                'required',
                'array',
                Rule::exists(Listing::class, 'id'),
            ],
        ];
    }
}
