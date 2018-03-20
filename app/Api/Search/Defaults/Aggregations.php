<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 08/08/2017
 * Time: 17:17
 */

namespace App\Api\Search\Defaults;


use App\Http\Requests\SearchTerms;
use Illuminate\Support\Collection;

class Aggregations
{
    public static function get()
    {
        return \GrizzlyViking\QueryBuilder\Branches\Factories\Aggregations::create(...config('search.aggregations'));
    }
}