<?php
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