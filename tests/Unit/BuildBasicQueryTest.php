<?php

namespace Tests\Unit;

use BoneCrusher\Api\Search\Book;
use BoneCrusher\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BuildBasicQueryTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
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

        $search = new Book(new QueryBuilder(), $parameters);

        $response = $search->search();
        dd($response->toJson());

        $this->assertTrue(!empty($search));
    }
}
