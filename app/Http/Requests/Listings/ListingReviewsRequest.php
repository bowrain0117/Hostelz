<?php

namespace App\Http\Requests\Listings;

use Illuminate\Foundation\Http\FormRequest;

class ListingReviewsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sortBy' => ['string'],
            'search' => ['string'],
            'page' => ['integer'],
        ];
    }
}
