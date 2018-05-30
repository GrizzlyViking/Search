<?php

namespace App\Providers;

use App\Api\Search\Blog;
use App\Api\Search\Book;
use App\Http\Requests\BlogRequest;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider,
    App\Api\Search\Book as BookSearch,
    GrizzlyViking\QueryBuilder\QueryBuilder,
    Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider,
    App\Http\Requests\SearchTerms,
    App\Api\Search\Defaults\Aggregations as DefaultFacades;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        $this->app->singleton(BookSearch::class, function ($app) {
            return (new BookSearch(
                $app->make(QueryBuilder::class),
                $app->make(SearchTerms::class)
            ))->setAggregates(DefaultFacades::get());
        });

        $this->app->singleton(Blog::class, function($app){
            return new Blog(
                $app->make(BlogRequest::class),
                $app->make(QueryBuilder::class)
            );
        });

        $this->app->singleton('ElasticSearch', function(){

            $host = [
                env('ELASTICSEARCH_HOST', 'localhost').':'.env('ELASTICSEARCH_PORT', 9200)
            ];

            return ClientBuilder::create()
                ->setHosts($host)
                ->build();
        });
    }
}
