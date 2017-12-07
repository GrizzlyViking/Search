<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 19/05/2017
 * Time: 13:50
 */

namespace App\Api\Search;

use App\Api\Search\Defaults\Aggregations as DefaultFacets;
use GrizzlyViking\QueryBuilder\Branches\Aggregations;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Filter;
use GrizzlyViking\QueryBuilder\Leaf\Factories\MultiMatch;
use GrizzlyViking\QueryBuilder\Branches\Factories\Queries;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Query;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use App\Http\Requests\SearchTerms;
use Illuminate\Support\Collection;
use GrizzlyViking\QueryBuilder\Response;

/*
|--------------------------------------------------------------------------
| Book Search
|--------------------------------------------------------------------------
|
| Just wanted to explain why I have made certain design decisions for the new iteration
| of Books Search. In no order that makes any sense;
| Aside from wanting to separate search out into its own API, I also wanted to get rid of
| renderer, and revert that job back to the books search, because jumping back and forth
| between then created too much confusion. Additionally the parameter validator/sanitiser
| has a much reduced remit. i.e. it 'just' validates the input, and sanitises a few things
| and it crucially no longer allocates elements to filters etc.
|
| The builder is separated to the extent, it is an composer importable package. The idea being that
| it could be used by other APIs without necessarily having a book focus... and what follows is that
| great care has been taken to make it as agnostic as possible. What I also wanted was that
| its the Elastic Search Builder that has the ability to make the actual search.
|
*/

class Book implements SearchInterface
{
    /** @var QueryBuilder */
    protected $builder;
    /** @var SearchTerms */
    protected $terms;
    protected $index = 'books_3';
    protected $type = 'book';
    /** @var Collection */
    protected $books;
    /** @var Collection */
    protected $resultMetaData;
    /** @var Response */
    protected $searchResults;

    public function __construct(QueryBuilder $queryBuilder, SearchTerms $terms)
    {
        $this->builder = $queryBuilder;
        $this->buildSearch($terms);
    }

    /**
     * @return Response
     */
    public function search(): Response
    {
        $this->searchResults = $this->builder->search();

        $this->applyCallbacksToAggregates();

        dd($this->builder->getAggregates());

        return $this->searchResults;
    }

    /**
     * @return Collection
     */
    public function getQuery(): Collection
    {
        return $this->builder->getQuery();
    }

    private function buildQuery($term)
    {
        $config = config('search.query');

        // interrogate config
        if (array_has($config, 'bool')) {
            if (count(array_get($config, 'bool')) > 1) {
                $booleans = array_get($config, 'bool');
                $queryBranch = new \GrizzlyViking\QueryBuilder\Branches\Queries();

                collect($booleans)->each(function($nodes, $boolean) use ($term, $queryBranch) {

                    foreach ($nodes as $node) {
                        if (array_has($node, 'multi_match')) {
                            $multiMatch = MultiMatch::create($term);
                            $multiMatch->setBoolean($boolean);
                            $multiConfig = array_get($node, 'multi_match');

                            if ($type = array_get($multiConfig, 'type', false)) {
                                $multiMatch->setMultiMatchType($type);
                            }

                            if ($operator = array_get($multiConfig, 'operator', false)) {
                                $multiMatch->setOperator($operator);
                            }

                            if ($analyzer = array_get($multiConfig, 'analyzer', false)) {
                                $multiMatch->setAnalyzer($analyzer);
                            }

                            if ($fields = array_get($multiConfig, 'fields', false)) {
                                $multiMatch->setFields($fields);
                            }

                            $queryBranch->add($multiMatch);
                        }
                    }
                });

                $this->builder->setQueries($queryBranch);
            }
        }
    }

    /**
     * @param SearchTerms $terms
     * @return $this
     */
    public function buildSearch(SearchTerms $terms): Book
    {
        $this->terms = collect($terms->validated());

        $this->terms->only(config('search.term'))->each(function ($term) {
            $this->buildQuery($term);
        });

        $this->terms->only(config('search.filters'))->each(function($filter, $key){
           $this->builder->setFilters(Filter::create([$key => $filter]));
        });

        $this->terms->only([config('search.orderBy'), config('search.pagination.resultsPerPageKey'), config('search.pagination.pageKey')])->each(function($option, $key) {
            switch ($key) {
                case config('search.orderBy'):
                    $this->builder->setSort($option);
                    break;
                case config('search.pagination.pageKey'):
                    $from = 0;

                    if ( ! $size = $this->terms->get(config('search.pagination.resultsPerPageKey'))) {

                        $size = config('search.pagination.resultsPerPageDefault');
                    }

                    if ($this->terms->has(config('search.pagination.pageKey'))) {
                        // x = page 3 * 30 results per page 90

                        $from = ($this->terms->get(config('search.pagination.resultsPerPageKey'))) * ($this->terms->get(config('search.pagination.pageKey')) - 1);
                    }
                    $this->builder->setSize($size, $from);
                    break;
            }
        });

        if (array_has(config('search'), 'script')) {
            $this->builder->setScript(config('search.script'));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function withFacets()
    {
        $this->setAggregates(DefaultFacets::get());

        return $this;
    }

    public function getResults()
    {
        return $this->builder->debug();
    }

    /**
     * Before Search! This is the aggregates building the query.
     *
     * @param Aggregations $aggregations
     * @return $this
     */
    public function setAggregates(Aggregations $aggregations): Book
    {
        $this->builder->setAggregates($aggregations);

        return $this;
    }

    /**
     * After Search! This is formatting the aggregates of the response
     *
     * @param string $aggregationKey
     * @param \Closure|null $callback
     */
    public function formatAggregation($aggregationKey, $callback = null)
    {
        if ($aggregation = $this->searchResults->getAggregations()->get($aggregationKey, false)) {
            $this->searchResults->setAggregation(
                $aggregationKey,
                $callback($aggregationKey, $aggregation)
            );
        }
        if (is_callable($callback)) {
            $callback = $this->defaultAggregationCallback();
        }
    }

    /**
     * @return \Closure
     */
    private function defaultAggregationCallback()
    {
        return function($aggregationKey, $aggregation) {

            /** @var \GrizzlyViking\QueryBuilder\Leaf\Aggregation $leaf */
            $leaf = $this->builder->getAggregates()->getLeaf($aggregationKey);
            $filters = $this->terms->only(config('search.filters'));
            $options = $aggregation;

            if ($buckets = array_get($aggregation, 'buckets', false)) {
                $options = collect($buckets)->flatMap(function($bucket, $key) {
                    return [
                        array_get($bucket, 'key', $key) => array_get($bucket, 'doc_count', $key)
                    ];
                });
            }

            return [
                'title' => ucwords($leaf->getTitle()),
                'name' => 'filter['.$leaf->getField().']',
                'anyLabel' => 'Any '.$leaf->getField(),
                'currentValue' => $filters->toArray(),
                'options' => $options->toArray()
            ];
        };
    }

    private function applyCallbacksToAggregates()
    {
        $this->builder->getAggregates()->getLeaves()->each(function($aggregation) { /** @var \GrizzlyViking\QueryBuilder\Leaf\Aggregation $aggregation */
            $this->formatAggregation(
                $aggregation->getTitle(),
                $aggregation->getCallback()
            );
        });
    }
}