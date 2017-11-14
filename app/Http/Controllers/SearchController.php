<?php

namespace App\Http\Controllers;

use App\Api\Search\Book;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Book $book)
    {
        return $book->withFacets()->search();
    }
}
