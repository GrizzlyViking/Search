<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 11/05/2018
 * Time: 16:48
 */

namespace App\Api\Search;


use App\Http\Requests\BlogRequest;
use Elasticsearch\Client;
use GrizzlyViking\QueryBuilder\Branches\Factories\Queries;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Query;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use App\Api\Search\Response\Blog as Response;
use GrizzlyViking\QueryBuilder\ResponseInterface;
use Illuminate\Support\Collection;

/**
 * Blogs are searched for in CMSSUGGESTIONS in LABEL, which returns a pageId,
 * Then the pageId can be found in MongoDB.pages
 *
 * Class Blog
 * @package App\Api\Search
 */
class Blog implements SearchInterface
{
    const INDEX = 'cmssuggestions';
    const TYPE = 'label';

    /** @var BlogRequest */
    protected $terms;
    /** @var QueryBuilder */
    protected $builder;

    public function __construct(BlogRequest $terms, QueryBuilder $builder)
    {
        $this->builder = $builder;
        $this->buildSearch($terms);
    }

    public function search(): ResponseInterface
    {
        $query = [
            'index' => self::INDEX,
            'type'  => self::TYPE,
            'body'  => $this->getQuery()
        ];

        /** @var Client $elastic */
        $elastic = app('ElasticSearch');
        $pageIds = $elastic->search($query);

        return new Response($pageIds);
    }

    public function getQuery(): Collection
    {
        return $this->builder->getQuery();
    }

    /**
     * @param \App\Http\Requests\BlogRequest $terms
     * @return Blog
     */
    protected function buildSearch($terms): Blog
    {

        /** @var \GrizzlyViking\QueryBuilder\Leaf\Query $leaf */
        $leaf = Query::create($terms->toArray());
        $leaf->setBoolean('must');
        $query = Queries::create($leaf);

        $this->builder->setQueries($query);

        return $this;
    }
}