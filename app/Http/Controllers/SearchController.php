<?php

namespace App\Http\Controllers;

use App\Api\Search\Book;
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
        return $this->book->withFacets()->search()->getIsbns();
    }

    public function category()
    {
        // TODO: return book search for a specific category
        return $this->book->withFacets()->search();
    }

    public function author()
    {
        // TODO: return book search for a specific author.
        return $this->book->withFacets()->search();
    }

    public function publisher()
    {
        // TODO: return book search for a specific publisher.
        return $this->book->withFacets()->search();
    }

    public function blog () {
        // TODO: return blog posts
        $query = new QueryBuilder();
        $query->setQueries(\GrizzlyViking\QueryBuilder\Leaf\Factories\Query::create(
            ['match' => ['blog' => 'Harry Potter']]
        ));

        return $query->getQuery()->toJson();
    }

    public function tags () {
        // TODO: return tags
    }

}
