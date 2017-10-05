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
            'type' => [
                'best_fields'
            ],
            'fields' => [
                'title.english_standard',
                'subtitle.english_standard',
                'headline',
                'contributors',
                'series',
                'publisher'
            ]
        ],

        'orderBy' => 'salesWeight',
        'pagination' => [
            'resultsPerPageKey' => 20,
            'pageKey' => 'page',
            'resultsPerPageDefault' => 20
        ]
    ];