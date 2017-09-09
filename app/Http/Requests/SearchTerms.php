<?php

namespace BoneCrusher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule,
    BoneCrusher\Rules\Utf8;

class SearchTerms extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'term' =>  ['sometimes', 'nullable','string', new Utf8()],
            'id' => 'sometimes|string',
            'ids' => 'sometimes|array',
            'ids.*' => 'sometimes|',
            'author' => 'sometimes|required|string',
            'publisher' => 'sometimes|required|string',
            'rank' => 'sometimes|required|numeric|min:0|max:5',
            'interestAge' => 'sometimes|required|string',
            'publicationDate' => [
                'sometimes',
                'required',
                Rule::in([
                    'Over a year ago',
                    'Within the last year',
                    'Within the last 3 months',
                    'Within the last month',
                    'Coming soon'
                ])
            ],
            'formats' => 'sometimes|required|string',
            'languages' => 'sometimes|required|string',
            'series' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'orderBy' => 'sometimes',
            'resultsPerPage' => 'sometimes|numeric|min:0',
            'page' => 'sometimes|integer|min:0|max:200'
        ];
    }
}
