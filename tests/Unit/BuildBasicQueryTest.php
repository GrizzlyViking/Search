<?php

namespace Tests\Unit;

use App\Api\Search\Book;
use App\Api\Search\Defaults\Aggregations;
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

        $parameters->validateResolved();

        $search = new Book(new QueryBuilder(), $parameters);

        $this->assertTrue(!empty($search));
    }

    /** @test */
    public function basic_query()
    {
        $this->mockRequest->shouldReceive('validated')->andReturn(['term' => 'Harry Potter']);

        $search = new Book(new QueryBuilder(), $this->mockRequest);

        $query = $search->withFacets()->search();

        $this->assertEquals(
            $query->getIsbns()->count(), $search->getQuery()->get('size'),
            'Isbns found and isbns set by config dont match.'
        );

        /** @var \GrizzlyViking\QueryBuilder\Branches\Aggregations $default_aggregations */
        $default_aggregations = Aggregations::get();
        $result1 = $query->getAggregations()->keys()->sortBy(function($value, $key){
            return strtolower($value);
        })->values();
        $result2 = $default_aggregations->getLeaves()->keys()->sortBy(function($value, $key){
            return strtolower($value);
        })->values();

        $this->assertEquals($result1, $result2, 'Comparing the aggregations in query with aggregations in response');

    }

    /** @test */
    public function parse_url_using_hyphens_and_double_hyphens()
    {
        $this->mockRequest->shouldReceive('validated')->andReturn(['term' => 'Harry Potter']);

        $search = new Book(new QueryBuilder(), $this->mockRequest);

        $response = $this->invokeMethod($search, 'parseUrlString', [['sebastian-Scheel--Edelmann']]);

        $this->assertEquals(["sebastian Scheel-Edelmann"], $response);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
