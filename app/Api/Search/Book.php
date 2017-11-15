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

class Book implements SearchInterface
{
    /** @var QueryBuilder */
    protected $builder;
    /** @var SearchTerms */
    protected $terms;

    public function __construct(QueryBuilder $queryBuilder, SearchTerms $terms)
    {
        $this->builder = $queryBuilder;
        $this->buildSearch($terms);
    }

    /**
     * @return Collection
     */
    public function search()
    {
        return $this->builder->getQuery();
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
     * @param Aggregations $aggregations
     * @return $this
     */
    public function setAggregates(Aggregations $aggregations)
    {
        $this->builder->setAggregates($aggregations);

        return $this;
    }
}