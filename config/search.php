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
        | Multi Match
        |--------------------------------------------------------------------------
        |
        | configure how the multimatch field is configured.
        |
        */
        'multiMatch' => [
            'type' => 'best_fields',
            'fields' => [
                'title.english_standard',
                'subtitle.english_standard',
                'headline',
                'contributors',
                'series',
                'publisher'
            ]
        ],

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
    ];