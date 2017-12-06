<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 30/11/2017
 * Time: 17:13
 */

namespace App\Api\Search;


class Response
{
    /** @var \Illuminate\Support\Collection */
    protected $books;
    protected $resultMetaData;

    public function __construct($elasticResponse)
    {
        if (array_has($elasticResponse, 'hits.total') && array_has($elasticResponse, 'hits.hits')) {
            $this->books = collect(array_get($elasticResponse, 'hits.hits'));
            $this->resultMetaData = collect(array_except($elasticResponse, ['hits.hits', 'aggregations']));
        }

        if ($aggregations = array_get($elasticResponse, 'aggregations', false)) {
            $this->aggregations = collect($aggregations);
        }
    }

    public function all()
    {

    }

    public function getIsbns()
    {
        return $this->books->pluck('_id');
    }
}