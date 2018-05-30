<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 23/08/2017
 * Time: 15:20
 */

namespace App\Api\Search;


use GrizzlyViking\QueryBuilder\ResponseInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

interface SearchInterface
{
    public function search(): ResponseInterface;

    public function getQuery(): Collection;
}