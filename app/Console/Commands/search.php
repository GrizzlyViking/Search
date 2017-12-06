<?php

namespace App\Console\Commands;

use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Illuminate\Console\Command;

class search extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:search {term}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for a particular term.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $term = new SearchTerms();
        $term->replace([
            'term' => $this->argument('term')
        ]);

        /** @var Book $book */
        $book = new Book(
            new QueryBuilder(),
            $term
        );

        return $book->search()->getIsbns();
    }
}
