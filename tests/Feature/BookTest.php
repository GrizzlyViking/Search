<?php

namespace Tests\Feature;

use BoneCrusher\Api\Search\Book;
use BoneCrusher\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBuildMultiMatch()
    {
        $bookSearch = $this->bookSearch([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        $multiMatch = collect(array_pluck(array_get($bookSearch->getQuery()->toArray(), 'query.must'), 'multi_match'));

        $this->assertTrue($multiMatch->contains('query', 'fire'), 'Multi match should have contained query => fire, but did not.');
        $this->assertTrue(is_string($multiMatch->get('type')), 'Multi Match type should be a string.');
    }

    public function testBuildSearch()
    {
        $bookSearch = $this->bookSearch([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        dd($bookSearch->getQuery()->toArray());

        $this->assertEquals('fire', array_get($bookSearch->getQuery()->toArray(), 'query.must.0.multi_match.query'));
    }

    public function bookSearch($parameters)
    {
        $searchTerms = $this->app->make(SearchTerms::class);

        $searchTerms->replace($parameters);

        $searchTerms->validate();

        return new Book(
            $this->app->make(QueryBuilder::class),
            $searchTerms
        );
    }
}
