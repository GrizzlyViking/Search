<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 11/07/2018
 * Time: 11:09
 */

namespace Tests\Feature;


use Tests\TestCase;

class ConfigTest extends TestCase
{
    /** @test */
    public function test_callback_for_interestAge_pre_query()
    {
        $callback = config('search.filter_callbacks.interestAge');
        $response = $callback(['babies']);
        $this->assertEquals([
            'range' => [
                'interestAge' => [
                    'lte'  => 1
                ]
            ]
        ],$response, 'Interest age babies does not produce the correct response.');

        $callback = config('search.filter_callbacks.interestAge');
        $response = $callback(['Toddlers']);
        $this->assertEquals([
            'range' => [
                'interestAge' => [
                    'gt'  => 1,
                    'lte' => 3
                ]
            ]
        ],$response, 'Interest age toddler does not produce the correct response.');

        $response = $callback(['2-16 years']);
        $this->assertEquals($response, [
            'range' => [
                'interestAge' => [
                    'gte'  => 2,
                    'lt' => 16
                ]
            ]
        ]);

        $response = $callback(['6-8 years', '13+ years']);
        $this->assertEquals(['should' => [
            [
                'range' => [
                    'interestAge' =>
                        [
                            'gte' => 6,
                            'lt'  => 8
                        ]

                ]
            ],
            [
                'range' => [
                    'interestAge' =>
                        [
                            'gte' => 13
                        ]

                ]
            ]
        ]],$response, 'two age ranges for interest group didn\'t output expected response.');
    }
}