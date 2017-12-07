<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 08/08/2017
 * Time: 17:17
 */

namespace App\Api\Search\Defaults;


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

                        $countryCode = 'GB';

                        $options = collect($value[$queriedAggregation['title']]['buckets'])->filter(function ($option) {
                            return $option['key'] == 0;
                        })->map(function ($option) use ($queriedAggregation, $countryCode) {
                            $option['label'] = $countryCode == 'GB' ? ($queriedAggregation['title']) : 'Ready To Go';
                            $option['value'] = 'express';
                            $option['count'] = $option['doc_count'];

                            return [$option];
                        })->first();

                        return [
                            'name' => 'leadTime[]',
                            'vanityLabels' => 'highlight',
                            'hideAll' => true,
                            'values' => isset($filters['leadTime']) && $filters['leadTime'] == 0 ? ['express'] : [],
                            'options' => $options ?: [],
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
                    'callback' => function($value, Collection $filters = null, $queriedAggregation = null) {

                        if ( ! $value instanceof Collection) {
                            if (isset($value[$queriedAggregation['title']]['buckets'])) {
                                $value = collect($value[$queriedAggregation['title']]['buckets']);
                            } elseif (isset($value['buckets'])) {
                                $value = collect($value['buckets']);
                            }
                        }

                        $clicked = $filters->has($queriedAggregation['field']) ? [$filters->get($queriedAggregation['field'])] : [];

                        // in place to catch if buckets are missing.
                        if ($value->isNotEmpty()) {

                            $options = $value->flatMap(function($option, $key) {

                                return [[
                                    'label' => $key,
                                    'value' => $key,
                                    'count' => $option['doc_count']
                                ]];
                            })->filter(function($option) use ($clicked) {
                                return ($option['count'] > 0 || in_array($option['value'], $clicked));
                            })->values()->toArray();
                        } else {
                            $options = [[
                                'label' => $clicked,
                                'value' => $clicked,
                                'count' => 0
                            ]];
                        }

                        return [
                            'title' => $queriedAggregation['title'],
                            'name' => $queriedAggregation['field'],
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