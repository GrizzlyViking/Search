<?php

use App\Http\Requests\SearchTerms;

return [

        'term' => 'term',

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        |
        | array of fields to filter against
        |
        */
        'field' => [
            'author'
        ],


        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        |
        | configure how the Query branch is configured.
        |
        */
        "query" => [
            "bool" => [
                "must"     => [
                    [
                        "multi_match" => [
                            "type"     => "cross_fields",
                            "operator" => "and",
                            "analyzer" => "english_std_analyzer",
                            "fields"   => [
                                "boostedFullText.english_no_tf^7",
                                "fullText.english_no_tf^2"
                            ]
                        ]
                    ]
                ],
                "should"   => [
                    [
                        "multi_match" => [
                            "fields"   => [
                                "boostedFullText.unstemmed_no_tf^7",
                                "fullText.unstemmed_no_tf^2"
                            ],
                            "operator" => "OR",
                            "type"     => "cross_fields",
                            "analyzer" => "unstemmed"
                        ]
                    ],
                    [
                        "multi_match" => [
                            "fields"   => [
                                "boostedFullText.english^7",
                                "fullText.english^2"
                            ],
                            "operator" => "OR",
                            "type"     => "phrase",
                            "analyzer" => "english_std_analyzer"
                        ]
                    ]
                ]
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Scripts
        |--------------------------------------------------------------------------
        |
        | This scores various weights to achieve a improved bestseller list.
        |
        */
        "script" => "(1 + Math.pow(_score, 0.5) * doc['scores.inStock'].value" .
            " * (" .
                "0.25 * doc['scores.sales30ALL'].value + " .
                "0.1 * doc['scores.sales90ALL'].value + " .
                "0.005 * doc['scores.sales180ALL'].value + " .
                "0.05 * doc['scores.leadTime'].value + " .
                "0.15 * doc['scores.readyToGo'].value + " .
                "0.01 * doc['scores.hasJacket'].value + " .
                "0.01 * doc['scores.hasGallery'].value" .
            "))",

        "score_mode" => "first",
        "boost_mode" => "replace",

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        |
        | Sort and Pagination keys. This is translating from request variables to ElasticSearch equivalents
        |
        */
        'orderBy' => 'orderBy',

        'pagination' => [
            'resultsPerPageKey' => 'resultsPerPage',
            'pageKey' => 'page',
            'resultsPerPageDefault' => 20
        ],

        /*
        |--------------------------------------------------------------------------
        | Post Filters
        |--------------------------------------------------------------------------
        |
        | These are the filters that are applied after the query has run. The significance
        | is that they can then be used to be applied selectively to aggregations.
        | ie, the filter does not apply to an aggregation on itself.
        |
        */
        'filters' => [
            'publisher',
            'series',
            'languages',
            'contributors',
            'interestAge',
            'tagIds',
            'rating',
            'formats',
            'websiteCategoryCodes'
        ],
        
        
        /*
        |--------------------------------------------------------------------------
        | Aggregations / Facets
        |--------------------------------------------------------------------------
        |
        | Aggregations have to be requested to be added. This is the defaults aggregations
        | used.
        |
        */
        'aggregations' => [
            [
                'title' => 'Express Delivery',
                'field' => 'leadTime',
                'callback' => function($aggregationKey, $aggregations) {

                    $bucket = collect(array_get($aggregations, 'buckets'))->first(function($bucket) {
                        return array_get($bucket, 'key', false) == 0;
                    });

                    $count = array_get($bucket, 'doc_count', 0);

                    return [
                        'name' => 'leadTime[]',
                        'vanityLabels' => 'highlight',
                        'hideAll' => true,
                        'values' =>  $count ? ['express'] : [],
                        'options' => [
                            'label' => 'GB',
                            'value' => 'express',
                            'count' => array_get($bucket, 'doc_count')
                        ],
                        'type' => 'check'
                    ];
                }
            ],
            [
                'title' => 'author',
                'field' => 'contributors.exact_matches_ci',
                'sampler' => ['shard_size' => 10000]
            ],
            [
                'title' => 'Age Group',
                'field' => 'interestAge',
                //'order' => ['interestAge' => 'asc'],
                'ranges' => [
                    ['key' => 'Babies', 'to' => 2],
                    ['key' => 'Toddlers', 'from' => 1, 'to' => 3],
                    ['key' => '3-5 years', 'from' => 3, 'to' => 6],
                    ['key' => '6-8 years', 'from' => 6, 'to' => 9],
                    ['key' => '9-12 years', 'from' => 9, 'to' => 13],
                    ['key' => '13+ years', 'from' => 13]
                ]
            ],
            [
                'title' => 'Publication Date',
                'field' => 'publicationDate',
                //'order' => ['publicationDate' => 'desc'],
                'ranges' => [
                    ['key' => 'Coming soon', 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+3 month'))],
                    ['key' => 'Within the last month', 'to' => date('Y-m-d'), 'from' => date('Y-m-d', strtotime('-1 month'))],
                    ['key' => 'Within the last 3 months', 'to' => date('Y-m-d'), 'from' => date('Y-m-d', strtotime('-3 month'))],
                    ['key' => 'Within the last year', 'to' => date('Y-m-d'), 'from' => date('Y-m-d', strtotime('-1 year'))],
                    ['key' => 'Over a year ago', 'to' => date('Y-m-d', strtotime('-1 year'))]
                ],
                'callback' => function($aggregationKey, $aggregations) {
                    $searchTerms = app(SearchTerms::class);

                    $clicked = $searchTerms->get('publicationDate', []);

                    $options = collect(array_get($aggregations, 'buckets'))->map(function($element, $key) {
                        return [
                            'label' => $key,
                            'value' => $key,
                            'count' => array_get($element, 'doc_count', 0)
                        ];
                    })->filter(function($option) use ($clicked) {
                        return ($option['count'] > 0 || in_array($option['value'], $clicked));
                    })->values()->toArray();

                    return [
                        'title' => 'Publication Date',
                        'name' => 'publicationDate',
                        'vanityLabels' => null,
                        'values' => $clicked,
                        'options' => $options,
                        'type' => 'radio'
                    ];
                }
            ],
            [
                'title' => 'formats',
                'field' => 'formatGroup.exact_matches_ci'
            ],
            [
                'title' => 'languages',
                'field' => 'languages'
            ],
            [
                'title' => 'series',
                'field' => 'series.exact_matches_ci'
            ],
            [
                'title' => 'publisher',
                'field' => 'publisher.exact_matches_ci'
            ],
            [
                'title' => 'rating',
                'field' => 'averageRating',
                'ranges' => [
                    ['key' => '1 star', 'from' => 0.01],
                    ['key' => '2 stars', 'from' => 1.5],
                    ['key' => '3 stars', 'from' => 2.5],
                    ['key' => '4 stars', 'from' => 3.5],
                    ['key' => '5 stars', 'from' => 4.5]
                ]
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | ElasticSearch Client
        |--------------------------------------------------------------------------
        |
        | The client itself gets the index setting from config/elasticsearch, which in
        | turn gets the settings from the .env file.
        |
        */
        'index' => [
            'index' => env('ELASTICSEARCH_INDEX','books'),
            'type' => env('ELASTICSEARCH_TYPE', 'book')
        ],
    ];