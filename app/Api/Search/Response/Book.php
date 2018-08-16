<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 14/08/2018
 * Time: 11:09
 */

namespace App\Api\Search\Response;


class Book extends \GrizzlyViking\QueryBuilder\Response
{
    public function getIsbnsAndAggregations()
    {
        return [
            'books' => $this->getIsbns(),
            'facets' => $this->getAggregations()
        ];
    }
}