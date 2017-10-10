<?php

namespace Tests\Feature;

use BoneCrusher\Http\Requests\SearchTerms;
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

        $parameters->validate();

        $this->assertContains(["term" => "fire"], $parameters->validated(), "term has not been cleansed of in-term filters.");
        $this->assertContains(["contributors" => "J K Rawlings"], $parameters->validated(), "in-term filters have not been allocated");
        $this->assertContains(["publisher" => "someone"], $parameters->validated(), "in-term filters have not been allocated.");
        $this->assertContains(["websiteCategoryCodes" => "Y"], $parameters->validated(), "Translation of 'categories' to 'websiteCategoryCodes' failed");
        $this->assertContains(["match" => "author"], $parameters->validated(), "match must be in a allowed amount of options");
    }
}