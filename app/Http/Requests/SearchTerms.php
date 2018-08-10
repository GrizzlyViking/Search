<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
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
            'series'          => 'sometimes|required|array',
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

    /**
     * @return Collection
     */
    public function translated(): Collection
    {
        return collect($this->toArray())->flatMap(function ($value, $key) {
            switch ($key) {
                case 'contributors':
                    if (count($value) >1) {

                        return [$key => ['should' => collect($value)->map(function($value) use ($key) {
                            return ['term' => [$key . '.exact_matches_ci' => $value]];
                        })->toArray()]];

                    } else {
                        return [$key => ['term' => [$key . '.exact_matches_ci' => $value]]];

                    }
                    break;
                case 'interestAge':
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $age_groups = collect($value)->map(function ($value) {
                        switch (strtolower($value)) {
                            case 'babies':
                                return [
                                    'lte' => 1
                                ];
                            case 'toddlers':
                            case 'toddler':
                                return [
                                    'gt'  => 1,
                                    'lte' => 3
                                ];
                            default:
                                if (preg_match('/(\d+)\-(\d+)/', $value, $matches)) {
                                    return [
                                        'gte' => $matches[1],
                                        'lt'  => $matches[2]
                                    ];
                                } elseif (preg_match('/(\d+)\+/', $value, $matches)) {
                                    return [
                                        'gte' => $matches[1]
                                    ];
                                } else {
                                    return ['gte' => 0];
                                }
                                break;
                        }
                    });

                    if ($age_groups->count() == 1) {
                        return [$key => [
                            'range' => [
                                'interestAge' => $age_groups->flatMap(function ($element) {
                                    return $element;
                                })->toArray()
                            ]
                        ]];
                    } else {
                        return [$key => [
                            'should' => $age_groups->map(function ($element) {

                                return ['range' => ['interestAge' => $element]];
                            })->toArray()
                        ]];
                    }

                    break;
                case 'publicationDate':
                    $options = [
                        'Coming soon'              => [
                            'lte' => date_create('9 months')->format('Y-m-d'),
                            'gte' => date_create('now')->format('Y-m-d')
                        ],
                        'Within the last month'    => [
                            'lte' => date_create('now')->format('Y-m-d'),
                            'gte' => date_create('-1 month')->format('Y-m-d')
                        ],
                        'Within the last 3 months' => [
                            'lte' => date_create('now')->format('Y-m-d'),
                            'gte' => date_create('-3 month')->format('Y-m-d')
                        ],
                        'Within the last year'     => [
                            'lte' => date_create('now')->format('Y-m-d'),
                            'gte' => date_create('-1 year')->format('Y-m-d')
                        ],
                        'Over a year ago'          => ['lte' => date_create('-1 year')->format('Y-m-d')]
                    ];

                    if (!isset(array_change_key_case($options, CASE_LOWER)[strtolower($value)])) {
                        return false;
                    }

                    return [$key => [
                        "range" => [
                            "publicationDate" => array_change_key_case($options, CASE_LOWER)[strtolower($value)]
                        ]
                    ]];
                    break;
                case 'forSale':
                    return [$key => ['term' => ['forSale' => $value]]];
                case 'country':
                    return [$key => ['must_not' => ['terms' => ['salesExclusions' => [$value]]]]];
                default:
                    return [$key => [$key => $value]];
            }
        });
    }
}
