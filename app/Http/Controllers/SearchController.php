<?php

namespace App\Http\Controllers;

use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Query;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;

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


    public function index(Book $book)
    {
        return $book
            ->withFacets()
            ->search()
            ->all();
            //->getResultMetaData();
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

    public function category(Book $book, $category)
    {
        $book->setMust(['category' => $category]);
        return $book->withFacets()->search();
    }

    public function publisher(Book $book, $publisher)
    {
        $book->setMust(['publisher' => $publisher]);
        return $book->withFacets()->search();
    }

    public function series(Book $book, $series)
    {
        $book->setMust(['series' => $series]);
        return $book->withFacets()->search();
    }

    public function tags () {
        // TODO: return tags
    }
}
