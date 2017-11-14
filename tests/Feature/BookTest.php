<?php

namespace Tests\Feature;

use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Tests\TestCase;

class BookTest extends TestCase
{
    /**
     * @test
     */
    public function build_the_search_query_branch()
    {
        $bookSearch = $this->bookSearch([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        $multi_match = collect(array_get($bookSearch->getQuery()->toArray(), 'query.multi_match'));

        $this->assertEquals('fire', $multi_match->get('query'), 'Multi match should have contained query => fire, but did not.');
        $this->assertEquals( config('search.multiMatch.type'), $multi_match->get('type'), 'Multi match should have contained type => '.config('search.multiMatch.type').', but did not.');
        $this->assertEquals(config('search.multiMatch.fields'), $multi_match->get('fields'));
    }

    /** @test */
    public function add_post_filters_correctly()
    {
        $bookSearch = $this->bookSearch([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        $post_filter = collect($bookSearch->getQuery()->get('post_filter')['must']);

        $this->assertTrue($post_filter->contains('match', ["formats" => "paperback"]), 'post filters does not contain formats => paperback, and should.');
        $this->assertTrue($post_filter->contains('match_phrase', ["contributors" => "J K Rawlings"]), 'post filters does not contain contributors => J K Rawlings, and should.');
        $this->assertTrue($post_filter->contains('match', ["publisher" => "someone"]), 'post filters does not contain publisher => someone, and should.');
        $this->assertTrue($post_filter->contains('match', ["websiteCategoryCodes" => "Y"]), 'post filters does not contain websiteCategoryCodes => Y, and should. "categories" should be translated in the request to "websiteCategoryCodes"');

        $this->assertNotTrue($post_filter->contains('match', ["redirect_uri" => "httpapisearchsebcallback"]), 'redirect_uri should not appear for 2 reasons, there is a filter white-list in config. and Requests\SearchTerms should validate it out too.');
        $this->assertNotTrue($post_filter->contains("term", ['term' =>"fire"]), 'post filters should not contain the query');
    }

    /** @test */
    public function apply_pagination_to_search()
    {
        $pages = 3;
        $resultsPerPage = 30;

        $bookSearch = $this->bookSearch([
            config('search.orderBy')   => 'SalesWeights',
            config('search.pagination.pageKey')    => $pages,
            config('search.pagination.resultsPerPageKey') => $resultsPerPage
        ]);

        $pagination = collect($bookSearch->getQuery()->toArray());

        $this->assertTrue($pagination->contains('SalesWeights', 'asc'), 'sort does not appear to have been applied');
        $this->assertEquals($resultsPerPage, $pagination->get('size'), 'Results per page incorrect');
        $this->assertEquals($resultsPerPage * ($pages - 1), $pagination->get('from'), 'start point in search wrong for pagination.');

    }

    /** @test */
    public function use_facets_in_query()
    {
        $booksearch = $this->bookSearch([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ])->withFacets();
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
