<?php

namespace Tests\Unit;

use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Mockery\Mock;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BuildBasicQueryTest extends TestCase
{
    /** @var  Mock */
    protected $mockRequest;

    public function setUp()
    {
        parent::setUp();

        $this->mockRequest = \Mockery::mock(SearchTerms::class);
    }

    public function tearDown()
    {
        unset($this->mockRequest);
    }
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testItBuilds()
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

        $this->assertTrue(!empty($search));
    }

    /** @test */
    public function basic_query()
    {
        $this->mockRequest->shouldReceive('validated')->andReturn(['term' => 'Harry Potter']);

        $search = new Book(new QueryBuilder(), $this->mockRequest);

        $query = $search->withFacets()->search()->all();

        dd($query);
    }
}
