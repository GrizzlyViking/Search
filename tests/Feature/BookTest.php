<?php

namespace Tests\Feature;

use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Filter;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Tests\TestCase;
use Wordery\TypeCodes\Categories;

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

        try {
            $parameters->validateResolved();
        } catch (\Illuminate\Validation\ValidationException $e) {
            dd($e->errors());
        }

        $parameters->messages();

        $bookSearch = new Book(new QueryBuilder(), $parameters);

        $multi_match = array_first(array_get($bookSearch->getQuery()->get('query'), 'function_score.query.bool.must'));

        $config_query_must = config('search.query.bool.must');

        $this->assertEquals('fire', array_get($multi_match, 'multi_match.query'), 'Multi match should have contained query => fire, but did not.');
        $this->assertEquals( array_get(array_first($config_query_must), 'multi_match.type'), array_get($multi_match, 'multi_match.type'), 'Multi match should have contained type => '.config('search.multiMatch.type').', but did not.');
        $this->assertEquals( array_get(array_first($config_query_must), 'multi_match.fields'), array_get($multi_match, 'multi_match.fields'));
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

        $parameters->validateResolved();

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

        $parameters->validateResolved();

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

        $parameters->validateResolved();

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

    /** @test */
    public function search_with_many_facets()
    {
        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace([
            'term'           => 'Harry Potter',
            'viewBy'         => 'grid', // this should not be validated
            'resultsPerPage' => 10,
            'page'           => 1,
            'contributors'   => 'j. k. rowlings',
            'interestAge'    => '9-12 years',
            'languages'      => 'eng'
        ]);

        $parameters->validateResolved();

        $bookSearch = (new Book(new QueryBuilder(), $parameters))->withFacets();

        $query = $bookSearch->getQuery()->toArray();

        // Check interest age is parsed correctly.
        $this->assertContains(['range'=> ["interestAge" => [ "gte" => "9", "lt" => "12" ]]], array_get($query, 'post_filter.bool.must'));

        // Check that contributor is j.k. rowling
        // check the filter on the aggregate filter, in this example I'm checking the Express Delivery filter
        $this->assertEquals([
            ["match_phrase" => ["contributors" => "j k rowlings"]],
            ["range" => ["interestAge" => ["gte" => "9","lt" => "12"]]],
            ["match" => ["languages" => "eng"]]
        ], array_get($query, 'aggregations.Express Delivery.filter.bool.must'));

        $this->assertTrue(true);
    }

    /** @test */
    public function add_query_filters_and_no_term_shouldnt_be_match_all()
    {
        /** @var Book $book */
        $book = app(Book::class);

        $query = $book
            ->addFilter(Filter::create('must', ['term' => [SearchTerms::CATEGORIES => Categories::getCodeFromCategoryName('Fiction')]])->queryFilter())
            ->addFilter(Filter::create('must_not', ['terms' => [ 'salesExclusions' => ['GB']]])->queryFilter())
            ->withFacets()
            ->getQuery();

        $this->assertNotEquals(['match_all' => (object)[]], $query->get('query'));
    }
}
