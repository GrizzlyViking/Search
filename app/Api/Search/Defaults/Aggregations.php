<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 08/08/2017
 * Time: 17:17
 */

namespace App\Api\Search\Defaults;


use App\Http\Requests\SearchTerms;
use Illuminate\Support\Collection;

class Aggregations
{
    public static function get()
    {
        return \GrizzlyViking\QueryBuilder\Branches\Factories\Aggregations::create(
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
                    'field' => 'contributors.exact_matches_ci'
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
            );
    }
}