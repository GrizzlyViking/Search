<?php

namespace BoneCrusher\Providers;

use Illuminate\Support\ServiceProvider,
    App\Api\Search\Book as BookSearch,
    GrizzlyViking\QueryBuilder\QueryBuilder,
    Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider,
    BoneCrusher\Http\Requests\SearchTerms,
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
    }
}
