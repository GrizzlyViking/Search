<?php

namespace Tests\Feature;

use App\Api\Search\Blog;
use App\Api\Search\Book;
use App\Http\Requests\BlogRequest;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Tests\TestCase;

class BlogTest extends TestCase
{
    /** @test */
    public function build_blog_search()
    {
        /** @var Book $bookSearch */
        $this->assertTrue(true);

        $request = $this->app->make(BlogRequest::class);
        $request->replace(['label' => 'Book of the Week']);


        $searchBlog = new Blog(
            $request,
            new QueryBuilder()
        );

        dd($searchBlog->search()->blogs());

        return $searchBlog->getQuery();

    }
}
