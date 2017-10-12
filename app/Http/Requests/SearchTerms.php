<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
        'tags'             => 'tagIds'
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

    /**
     * @return void
     */
    protected function prepareForValidation()
    {
        if (! empty($input = $this->input())) {
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
            })->toArray();

            $this->replace($input);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'term'            => ['sometimes', 'nullable', 'string', new Utf8()],
            'id'              => 'sometimes|string',
            'ids'             => 'sometimes|array',
            'ids.*'           => 'sometimes|integer',
            'contributors'    => 'sometimes|required|string',
            'publisher'       => 'sometimes|required|string',
            'rank'            => 'sometimes|required|numeric|min:0|max:5',
            'interestAge'     => 'sometimes|required|string',
            'formats'         => 'sometimes|required|string',
            'languages'       => 'sometimes|required|string',
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
