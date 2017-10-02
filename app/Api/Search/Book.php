<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 19/05/2017
 * Time: 13:50
 */

namespace BoneCrusher\Api\Search;

use GrizzlyViking\QueryBuilder\Branches\Factories\Aggregations;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Filter;
use GrizzlyViking\QueryBuilder\Leaf\Factories\MultiMatch;
use GrizzlyViking\QueryBuilder\Branches\Factories\Queries;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use BoneCrusher\Http\Requests\SearchTerms;
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
     * @return array
     */
    public function search()
    {
        return $this->builder->getQuery();
    }

    public function buildSearch(SearchTerms $terms)
    {
        $this->terms = collect($terms->all());

        $this->terms->only(config('search.term'))->each(function($term) {
            $multiMatch = MultiMatch::create($term);
            $multiMatch->setMultiMatchType(config('search.multiMatch.type'));
            $multiMatch->setFields(config('search.multiMatch.fields'));

            $this->builder->setQueries(Queries::create($multiMatch));
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
                    $size = config('search.pagination.resultsPerPageDefault');
                    if ($this->terms->has(config('search.pagination.resultsPerPageKey'))) {
                        // x = page 3 * 30 results per page 90

                        $from = ($this->terms->get(config('search.pagination.resultsPerPageKey'))) * ($option - 1);
                    }
                    $this->builder->setSize($size, $from);
                    break;
            }
        });
    }

    public function getResults()
    {
        return $this->builder->debug();
    }

    public function setAggregates(\GrizzlyViking\QueryBuilder\Branches\Aggregations $aggregations)
    {
        $this->builder->setAggregates($aggregations);

        return $this;
    }
}