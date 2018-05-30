<?php

namespace App\Http\Controllers;

use App\Api\Search\Blog;
use App\Http\Requests\BlogRequest;

class BlogController extends Controller
{
    public function index(Blog $blog)
    {
        return $blog->search()->blogs();
    }
}
