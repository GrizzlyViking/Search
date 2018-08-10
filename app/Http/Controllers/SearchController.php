<?php

namespace App\Http\Controllers;

use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Filter;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Query;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use Wordery\TypeCodes\Categories;

class SearchController extends Controller
{
    /** @var Book */
    protected $book;

    /**
     * SearchController constructor.
     * @param Book $book
     */
    public function __construct(Book $book)
    {
        $this->book = $book;
    }


    /**
     * @param Book $book
     * @param string $countryCode
     * @return \Illuminate\Support\Collection|array
     */
    public function index(Book $book, string $countryCode)
    {
        return $book
            ->addFilter(Filter::create('must_not',
                ['terms' => ['salesExclusions' => [strtoupper($countryCode)]]])->queryFilter())
            ->withFacets()
            ->getQuery();
        //->search()
        //->all();
    }

    /**
     * @param Book $book
     * @param $author
     * @return \Illuminate\Support\Collection
     */
    public function author(Book $book, $author)
    {
        $book->setMust(['contributors' => $author]);

        return $book->search()->getIsbns();
    }

    public function category(Book $book, string $countryCode, string $category)
    {
        return $book
            ->category($category)
            ->country($countryCode)
            ->onlyAvailable()
            ->withFacets()
            ->getQuery();
    }

    public function publisher(Book $book, string $countryCode, string $publisher)
    {
        return $book
            ->country($countryCode)
            ->publisher($publisher)
            ->onlyAvailable()
            ->withFacets()->search()->all();
    }

    public function series(Book $book, string $countryCode, string $series)
    {
        return $book
            ->country($countryCode)
            ->series($series)
            ->onlyAvailable()
            ->withFacets()->search()->all();
    }
}
