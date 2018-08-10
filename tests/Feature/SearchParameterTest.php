<?php

namespace Tests\Feature;

use App\Http\Requests\SearchTerms;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchParameterTest extends TestCase
{
    /**
     * Testing that certain terms are correctly parsed. These are terms obtained from the original ElasticSearch
     * Parameters
     *
     * @return void
     */
    public function testParameters()
    {
        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace($post = [
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'match'      => 'author'
        ]);

        $parameters->validateResolved();

        $this->assertContains(["term" => "fire"], $parameters->validated(), "term has not been cleansed of in-term filters.");
        $this->assertContains(["contributors" => "J K Rawlings"], $parameters->validated(), "in-term filters have not been allocated");
        $this->assertContains(["publisher" => "someone"], $parameters->validated(), "in-term filters have not been allocated.");
        $this->assertContains(["websiteCategoryCodes" => "Y"], $parameters->validated(), "Translation of 'categories' to 'websiteCategoryCodes' failed");
        $this->assertContains(["match" => "author"], $parameters->validated(), "match must be in a allowed amount of options");
    }

    /** @test */
    public function using_translated_parameters()
    {
        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace([
            'viewBy'          => 'grid',
            'resultsPerPage'  => 10,
            'page'            => 1,
            'contributors'    => ['ian fleming', 'joe berger'],
            'interestAge'     => ['6-9 years', '9-12 years'],
            'publisher'       => ['pan macmillan'],
            'publicationDate' => 'Within the last year',
            'series'          => ["macmillan children's classics"],
            'country'         => 'GB'
        ]);

        $parameters->validateResolved();

        $response = $parameters->translated();


        $this->assertEquals(["viewBy" => "grid"], $response->get('viewBy'));
        $this->assertEquals(["resultsPerPage" => 10], $response->get('resultsPerPage'));
        $this->assertEquals(["page" => 1], $response->get('page'));
        $this->assertEquals([
                "should" => [
                    [
                        "term" => [
                            "contributors.exact_matches_ci" => "ian fleming"
                        ]
                    ],
                    [
                        "term" => [
                            "contributors.exact_matches_ci" => "joe berger"
                        ]
                    ]

            ]
        ], $response->get('contributors'));
        $this->assertEquals([
                "should" => [
                    [
                        "range" => [
                            "interestAge" => [
                                "gte" => "6",
                                "lt"  => "9"
                            ]
                        ]
                    ],
                    [
                        "range" => [
                            "interestAge" => [
                                "gte" => "9",
                                "lt"  => "12"
                            ]
                        ]
                    ]

            ]
        ], $response->get('interestAge'));
    }
}
