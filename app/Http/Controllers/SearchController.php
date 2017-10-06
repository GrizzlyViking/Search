<?php

namespace BoneCrusher\Http\Controllers;

use BoneCrusher\Api\Search\Book;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Book $book)
    {
        return $book->search();
    }
}
