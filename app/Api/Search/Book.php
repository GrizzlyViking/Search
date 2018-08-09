<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 19/05/2017
 * Time: 13:50
 */

namespace App\Api\Search;

use App\Api\Search\Defaults\Aggregations as DefaultFacets;
use Elasticsearch\Client;
use GrizzlyViking\QueryBuilder\Branches\Aggregations;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Filter;
use GrizzlyViking\QueryBuilder\Leaf\Factories\MultiMatch;
use GrizzlyViking\QueryBuilder\Branches\Factories\Queries;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Query;
use GrizzlyViking\QueryBuilder\Leaf\LeafInterface;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\ResponseInterface;
use Illuminate\Foundation\Http\FormRequest;
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
    /** @var string */
    protected $index = 'books';
    /** @var string */
    protected $type = 'book';
    /** @var Collection */
    protected $books;
    /** @var Collection */
    protected $resultMetaData;
    /** @var Response */
    protected $searchResults;

    /**
     * Book constructor.
     * @param QueryBuilder $queryBuilder
     * @param SearchTerms $terms
     */
    public function __construct(QueryBuilder $queryBuilder, SearchTerms $terms)
    {
        $this->builder = $queryBuilder;
        $this->buildSearch($terms);
    }

    /**
     * @param string $index
     * @return Book
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @param string $type
     * @return Book
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Response
     */
    public function search(): ResponseInterface
    {
        $query = [
            'index' => $this->getIndex(),
            'type'  => $this->getType(),
            'body'  => $this->getQuery()
        ];

        /** @var Client $database */
        $database = app('ElasticSearch');

        $this->searchResults = new Response($database->search($query));

        $this->applyCallbacksToAggregates();

        return $this->searchResults;
    }

    /**
     * @return Collection
     */
    public function getQuery(): Collection
    {
        return $this->builder->getQuery();
    }

    /**
     * @param array $query
     */
    public function setMust(array $query)
    {
        $query = Query::create($this->parseUrlString($query));
        $query->setBoolean('must');
        $branch = Queries::create($query);
        $this->builder->setQueries($branch);

        return $this;
    }

    /**
     * @param array $query
     * @return array
     */
    private function parseUrlString(array $query): array
    {
        return collect($query)->map(function($element) {

            if (count($array = explode('--',$element))>1) {
                $return = [];
                foreach ($array as $string) {
                    $return[] = str_replace(['_', '-'], ' ', $string);
                }

                return implode('-', $return);
            } else {

                return str_replace(['_', '-'], ' ', $element);
            }

        })->toArray();
    }

    /**
     * @param string $term
     */
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
    public function buildSearch(FormRequest $terms): SearchInterface
    {
        $this->terms = collect($terms->validated());

        $this->terms->only(config('search.term'))->each(function ($term) {
            $this->buildQuery($term);
        });

        $this->terms->only(config('search.filters'))->each(function($filter, $key) {

            $filter = $this->buildFilter($filter, $key);

            $this->builder->setFilters($filter);
        });

        $this->terms->only(config('search.query_filters'))->each(function($filter, $key) {

            $filter = $this->buildFilter($filter, $key, 'filter');

            $this->builder->setFilters($filter);
        });



        $this->terms->only([config('search.orderBy'), config('search.pagination.resultsPerPageKey'), config('search.pagination.pageKey')])->each(function($option, $key) {
            switch ($key) {
                case config('search.orderBy'):
                    $this->builder->setSort($option);
                    break;
                case config('search.pagination.pageKey'):
                    $from = 0;

                    if ( ! $size = $this->terms->get(config('search.pagination.resultsPerPageKey'), false)) {

                        $size = config('search.pagination.resultsPerPageDefault');
                    }

                    if ($this->terms->has(config('search.pagination.pageKey'))) {
                        // x = page 3 * 30 results per page 90

                        $from = ($size * ($this->terms->get(config('search.pagination.pageKey'), 1) - 1));
                    }

                    $this->builder->setSize($size, $from);
                    break;
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        |
        | If pagination has not been set by the request, then it is populated by the config.
        |
        */
        if ($this->builder->getPagination()->isEmpty()) {
          $this->builder->setSize(config('search.pagination.resultsPerPageDefault'), 0);
        }

        if ($functions = config('search.functions', false)) {
            $this->builder->setScript($functions);
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
        if (!is_callable($callback)) {
            $callback = $this->defaultAggregationCallback();
        }

        if ($aggregation = $this->searchResults->getAggregations()->get($aggregationKey, false)) {
            $this->searchResults->setAggregation(
                $aggregationKey,
                $callback($aggregationKey, $aggregation)
            );
        }
    }

    /**
     * @return \Closure
     */
    private function defaultAggregationCallback()
    {
        /**
         * @param $aggregationKey
         * @param $aggregation
         * @return array
         */
        return function ($aggregationKey, $aggregation) {

            /** @var \GrizzlyViking\QueryBuilder\Leaf\Aggregation $leaf */
            $leaf = $this->builder->getAggregates()->getLeaf($aggregationKey);
            $aggregation_config = collect(config('search.aggregations'))->first(function($agg) use ($leaf) {
                return array_get($agg, 'title', false) == $leaf->getTitle();
            });

            $filters = $this->terms->only(config('search.filters'));
            /** @var Collection $buckets */
            $buckets = collect($aggregation)->multiDimensionalGet('buckets');

            $options = $buckets->reject(function($value, $key) {
                return array_get($value, 'doc_count', false) === 0;
            })->flatMap(function ($bucket, $key) {
                return [[
                    'count' => array_get($bucket, 'doc_count', $key),
                    'label' => ucwords(array_get($bucket, 'key', $key)),
                    'value' => array_get($bucket, 'key', $key)
                ]];
            });

            $sanitizedFilter = preg_replace('/^([a-zA-Z0-9]+)\..*$/', "$1",$leaf->getField());

            $return = [
                'title'        => ucwords($leaf->getTitle()),
                'name'         => $sanitizedFilter.'[]',
                'anyLabel'     => 'Any ' . $sanitizedFilter,
                'currentValue' => $filters->get($sanitizedFilter, []),
                'options'      => $options->toArray()
            ];

            if ($type = array_get($aggregation_config, 'type', false)) {
                $return['type'] = $type;
            }

            return $return;
        };
    }

    /**
     * @return void
     */
    private function applyCallbacksToAggregates()
    {
        $this->builder->getAggregates()->getLeaves()->each(function($aggregation) { /** @var \GrizzlyViking\QueryBuilder\Leaf\Aggregation $aggregation */
            $this->formatAggregation(
                $aggregation->getTitle(),
                $aggregation->getCallback()
            );
        });
    }

    /**
     * @param $filter
     * @param $key
     * @return \GrizzlyViking\QueryBuilder\Leaf\Filter
     */
    private function buildFilter($filter, $key, $attachPoint = 'post_filter'): \GrizzlyViking\QueryBuilder\Leaf\Filter
    {
        // Applies callbacks intended for the query prior to execution.
        if ($callback = config('search.filter_callbacks.' . $key, false)) {

            $filter = $callback($filter);

            $filter = Filter::create($filter);
        } elseif (in_array($key, config('search.should_filters')) && is_array($filter) && count($filter) >= 2) {
            $filter = Filter::create(['should' => collect($filter)->map(function($filter) use ($key) {
                return [$key => $filter];
            } )->toArray()]);
        } else {
            $filter = Filter::create([$key => $filter]);
        }

        if (!in_array($attachPoint, ['query_filter','filter', 'post_filter'])) {
            throw new \InvalidArgumentException('Attachpoint for buildFilter invalid');
        }

        if (in_array($attachPoint, ['query_filter', 'filter'])) {
            $filter->setAttachPoint('filter');
        }

        return $filter;
    }

    /**
     * @param LeafInterface $filter
     * @return Book
     */
    public function addFilter($filter)
    {
        $this->builder->setFilters($filter);

        return $this;
    }
}