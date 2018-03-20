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
        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        $parameters->validate();

        $bookSearch = new Book(new QueryBuilder(), $parameters);

        $multi_match = array_first(array_get($bookSearch->getQuery()->get('query'), 'function_score.query.bool.must'));

        $config_query_must = config('search.query.bool.must');

        $this->assertEquals('fire', array_get($multi_match, 'multi_match.query'), 'Multi match should have contained query => fire, but did not.');
        $this->assertEquals( array_get(array_first($config_query_must), 'multi_match.type'), array_get($multi_match, 'multi_match.type'), 'Multi match should have contained type => '.config('search.multiMatch.type').', but did not.');
        $this->assertEquals(array_get(array_first($config_query_must), 'multi_match.fields'), array_get($multi_match, 'multi_match.fields'));
    }

    /** @test */
    public function add_post_filters_correctly()
    {
        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        $parameters->validate();

        $bookSearch = new Book(new QueryBuilder(), $parameters);

        $post_filter = collect(array_get($bookSearch->getQuery(), 'post_filter.bool.must'));

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
        $parameters = $this->app->make(SearchTerms::class);

        $pages = 3;
        $resultsPerPage = 30;
        $parameters->replace([
            config('search.orderBy')   => 'SalesWeights',
            config('search.pagination.pageKey')    => $pages,
            config('search.pagination.resultsPerPageKey') => $resultsPerPage
        ]);

        $parameters->validate();

        $bookSearch = new Book(new QueryBuilder(), $parameters);

        $pagination = collect($bookSearch->getQuery()->toArray());

        $this->assertTrue($pagination->contains('SalesWeights', 'asc'), 'sort does not appear to have been applied');
        $this->assertEquals($resultsPerPage, $pagination->get('size'), 'Results per page incorrect');
        $this->assertEquals($resultsPerPage * ($pages - 1), $pagination->get('from'), 'start point in search wrong for pagination.');

    }

    /** @test */
    public function use_facets_in_query()
    {
        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace([
            'term'       => 'fire publisher:someone author:"J K Rawlings"',
            'formats'    => 'paperback',
            'recent'     => true,
            'categories' => 'Y',
            'redirect_uri' => 'http://api.search.seb/callback',
            'match'      => 'author'
        ]);

        $parameters->validate();

        $bookSearch = (new Book(new QueryBuilder(), $parameters))->withFacets();

        $facets = collect(array_get($bookSearch->getQuery()->toArray(), 'aggregations'));

        $facets_from_config = collect(config('search.aggregations'));

        $this->assertEquals($facets->keys(), $facets_from_config->map(function($element){
            return array_get($element, 'title');
        }), "The Facet titles from config where compared with the aggregations in the query, they should be the same, they are not.");

        $this->assertFalse($facets_from_config->map(function($facet){
            return array_get($facet, 'callback', false);
        })->filter()->contains(function($callback){
            return ! is_callable($callback);
        }), "The facets in config contain a callback, which is not callable.");
    }
}
