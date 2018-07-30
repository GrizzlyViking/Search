<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule,
    App\Rules\Utf8;

class SearchTerms extends FormRequest
{
    const CATEGORIES = 'websiteCategoryCodes';
    protected static $_translations = [
        'id'               => '_id',
        'ID'               => '_id',
        'forsale'          => 'forSale',
        'othersPurchased'  => '_id',
        'searchFormat'     => 'formats',
        'deliveryOptions'  => 'leadTime',
        'Express Delivery' => 'leadTime',
        'format'           => 'formats',
        'interestAges'     => 'interestAge',
        'interest Ages'    => 'interestAge',
        'Age Group'        => 'interestAge',
        'categories'       => self::CATEGORIES,
        'category'         => self::CATEGORIES,
        'mainCategory'     => self::CATEGORIES,
        'language'         => 'languages',
        'author'           => 'contributors',
        'contributor'      => 'contributors',
        'tags'             => 'tagIds',
        'geoipCountryCode' => 'country'
    ];

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
     * @param string $key
     * @return string
     */
    private function translate($key)
    {
        if (isset(static::$_translations[$key])) {
            return static::$_translations[$key];
        }

        return $key;
    }

    public function add($key, $value)
    {
        $input = $this->input();
        $input[$key] = $value;

        $this->replace($input);
    }

    /**
     * @return void
     */
    protected function prepareForValidation()
    {
        if (! empty($input = $this->input())) {
            // TODO: this does not seem to be getting through, and very fecking uncertain about this being injected here.
            switch (true) {
                case isset($input['term']) && is_string($input['term']) && strlen($input['term']) > 1 && (strpos($input['term'], ':') !== false):
                    /**
                     * This is to restructure term, if the xxx:xxx notation is used.
                     */
                    if (preg_match_all('/[a-zA-Z0-9]+:("[a-zA-Z0-9\s]+"|[a-zA-Z]+)/', $input['term'], $matched)) {
                        $found = array_shift($matched);
                        $input['term'] = preg_replace('/\s{2}/', '', str_replace($found, '', $input['term']));
                        foreach ($found as $item) {
                            list($key, $value) = explode(':', $item);
                            if (! in_array($key, ['publisher', 'author', self::CATEGORIES, 'languages'])) {
                                continue;
                            }
                            $input[$key] = trim($value, '"');
                        }

                    }
                    break;
            }

            $input = collect($input)->flatMap(function($value, $key) {
                return [$this->translate($key) => $value];
            })->map(function ($input, $key) {
                    if (in_array($key, ['contributors', 'publisher', 'formats', 'interestAge', 'formatGroup']) && is_string($input)) {
                        return [$input];
                    }

                    return $input;
            })->toArray();

            $this->replace($input);
        }
    }

    public function messages()
    {
        return [
            'string' => "When :attribute is provided then it must be a string.",
            'array' => "When :attribute is provided then it must be an array.",
            'required' => ":attribute is required",
            'numeric' => "When :attribute is provided then it must be numeric."
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // TODO: formatGroup, how to deal with that.
        return [
            'term'            => ['sometimes', 'nullable', 'string', new Utf8()],
            'id'              => 'sometimes|string',
            'ids'             => 'sometimes|array',
            'ids.*'           => 'sometimes|integer',
            'contributors'    => 'sometimes|required|array',
            'publisher'       => 'sometimes|required|array',
            'rank'            => 'sometimes|required|numeric|min:0|max:5',
            'interestAge'     => 'sometimes|required|array',
            'formats'         => 'sometimes|required|array',
            'formatGroup'     => 'sometimes|required|array',
            'languages'       => 'sometimes|required|string',
            'country'         => 'sometimes|required|string',
            'series'          => 'sometimes|required|string',
            self::CATEGORIES  => 'sometimes|required|string',
            'match'           => [
                'sometimes',
                'required',
                Rule::in([
                    'author',
                    'publisher',
                    'contributor',
                    'series'
                ])
            ],
            'recent'          => 'sometimes|boolean',
            'orderBy'         => 'sometimes',
            'resultsPerPage'  => 'sometimes|numeric|min:0',
            'page'            => 'sometimes|integer|min:0|max:200',
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
            ]
        ];
    }
}
