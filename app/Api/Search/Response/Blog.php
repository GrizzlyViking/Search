<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 16/05/2018
 * Time: 12:24
 */

namespace App\Api\Search\Response;

use App\Models\Page;
use GrizzlyViking\QueryBuilder\Response;

class Blog extends Response
{
    protected $results;

    public function __construct($elasticResponse)
    {
        if (array_has($elasticResponse, 'hits.total') && array_has($elasticResponse, 'hits.hits')) {
            $this->results = collect(array_get($elasticResponse, 'hits.hits'))->map(function($element){
                return array_get($element, '_source');
            });
            $this->resultMetaData = collect(array_except($elasticResponse, ['hits.hits', 'aggregations']));
        }
    }

    public function all()
    {
        return $this->results;
    }

    public function pageIds()
    {
        return $this->results->pluck('pageId');
    }

    /**
     *
     */
    public function blogs()
    {
        $pageids = $this->pageIds();

        return Page::whereIn('pageId', $pageids)->where('publishedStatus', 'published')->get()->toArray();

    }
}