<?php

namespace App\Providers;

use App\Api\Search\Blog;
use App\Api\Search\Book;
use App\Http\Requests\BlogRequest;
use Elasticsearch\ClientBuilder;
use GrizzlyViking\QueryBuilder\Branches\Factories\Queries;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Filter;
use GrizzlyViking\QueryBuilder\Leaf\Factories\Query;
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

        $this->app->singleton(BookSearch::class, function ($app) { /** @var \Illuminate\Foundation\Application $app */
            /** @var QueryBuilder $build */
            $build = $app->make(QueryBuilder::class);
            $build->setFilters(Filter::create(['term' => ['forSale' => 1]])->queryFilter());

            $search = new BookSearch(
                $build,
                $app->make(SearchTerms::class)
            );
            return $search
                ->setIndex(config('search.index.index', 'books'))
                ->setType(config('search.index.type', 'books'))
                ->setAggregates(DefaultFacades::get());
        });

        $this->app->singleton(Blog::class, function($app){ /** @var \Illuminate\Foundation\Application $app */
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

        Collection::macro('multiDimensionalGet', function ($key, $default = null) {

            if (!$this instanceof Collection) {
                $clone = collect($this);
            } else {
                $clone = $this;
            }
            if ($clone->isEmpty()) {
                return $default ?? collect([]);
            }
            if ($clone->has($key)) {
                return collect($clone->get($key));
            }

            return $clone->flatMap(function ($value) {
                return $value;
            })->filter(function ($element) {
                return is_array($element);
            })->multiDimensionalGet($key, $default);
        });

        /** @return Collection */
        Collection::macro('mergeRecursive', function($array) {
            return collect(array_merge_recursive($this->toArray(), $array));
        });
    }
}
