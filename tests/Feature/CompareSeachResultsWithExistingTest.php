<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 04/07/2018
 * Time: 11:52
 */

namespace Tests\Feature;


use App\Api\Search\Book;
use App\Http\Requests\SearchTerms;
use GrizzlyViking\QueryBuilder\QueryBuilder;
use Tests\TestCase;

class CompareSeachResultsWithExistingTest extends TestCase
{
    /** @test */
    public function compare_harry_potter_search_with_many_facets()
    {
        /*
        |--------------------------------------------------------------------------
        | Harry Potter Search
        |--------------------------------------------------------------------------
        |
        | original GET: https://dotcomseb.wordery.com/search?viewBy=grid&resultsPerPage=10&term=harry+Potter&page=1&contributors[]=j.%20k.%20rowling&interestAge[]=6-8%20years&formatGroup[]=paperback&publisher[]=scholastic%20us
        | existing query: {"_source":["isbn13"],"query":{"function_score":{"query":{"bool":{"must":[{"multi_match":{"query":"harry Potter","type":"cross_fields","operator":"and","analyzer":"english_std_analyzer","fields":["boostedFullText.english_no_norm^7","fullText.english_no_norm^2"]}}],"should":{"multi_match":{"fields":["boostedFullText.unstemmed_no_norm^7","fullText.unstemmed_no_norm^2"],"operator":"OR","type":"cross_fields","analyzer":"unstemmed","query":"harry Potter"}},"must_not":[{"terms":{"salesExclusions":["GB"]}}]}},"functions":[{"script_score":{"script":"(1 + Math.pow(_score, 0.5)  * doc['scores.inStock'].value * (\n                    0.25 * doc['scores.sales30ALL'].value + \n                    0.1 * doc['scores.sales90ALL'].value + \n                    0.005 * doc['scores.sales180ALL'].value + \n                    0.05 * doc['scores.leadTime'].value + \n                    0.25 * doc['scores.readyToGo'].value + \n                    0.01 * doc['scores.hasJacket'].value + \n                    0.01 * doc['scores.hasGallery'].value  \n                    ))"}}],"score_mode":"first","boost_mode":"replace"}},"size":10,"post_filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"Express Delivery":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}},{"term":{"forSale":1}}]}},"aggregations":{"Express Delivery":{"terms":{"field":"leadTime"}}}},"author":{"filter":{"bool":{"must":[{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"author":{"sampler":{"shard_size":10000},"aggs":{"author":{"terms":{"size":10,"field":"contributors.exact_matches_ci","execution_hint":"global_ordinals"}}}}}},"Age Group":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"Age Group":{"range":{"field":"interestAge","keyed":true,"ranges":[{"key":"Babies","to":2},{"key":"Toddlers","to":3,"from":1},{"key":"3-5 years","to":6,"from":3},{"key":"6-8 years","to":9,"from":6},{"key":"9-12 years","to":13,"from":9},{"key":"13+ years","from":13}]}}}},"Publication Date":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"Publication Date":{"range":{"field":"publicationDate","keyed":true,"ranges":[{"key":"Coming soon","to":"2018-10-04","from":"2018-07-04"},{"key":"Within the last month","to":"2018-07-04","from":"2018-06-04"},{"key":"Within the last 3 months","to":"2018-07-04","from":"2018-04-04"},{"key":"Within the last year","to":"2018-07-04","from":"2017-07-04"},{"key":"Over a year ago","to":"2017-07-04"}]}}}},"formats":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"formats":{"terms":{"field":"formatGroup.exact_matches_ci"}}}},"languages":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"languages":{"terms":{"field":"languages"}}}},"series":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"series":{"sampler":{"shard_size":10000},"aggs":{"series":{"terms":{"size":10,"field":"series.exact_matches_ci","execution_hint":"global_ordinals"}}}}}},"publisher":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}}]}},"aggregations":{"publisher":{"sampler":{"shard_size":10000},"aggs":{"publisher":{"terms":{"size":10,"field":"publisher.exact_matches_ci","execution_hint":"global_ordinals"}}}}}},"rating":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"rating":{"range":{"field":"averageRating","keyed":true,"ranges":[{"key":"1 star","from":0.01},{"key":"2 stars","from":1.5},{"key":"3 stars","from":2.5},{"key":"4 stars","from":3.5},{"key":"5 stars","from":4.5}]}}}},"websiteCategoryCodes":{"filter":{"bool":{"must":[{"match_phrase":{"contributors":"j. k. rowling"}},{"bool":{"should":[{"range":{"interestAge":{"gte":6,"lt":9}}}]}},{"bool":{"should":[{"term":{"formatGroup.exact_matches_ci":"paperback"}}]}},{"match_phrase":{"publisher":"scholastic us"}}]}},"aggregations":{"websiteCategoryCodes":{"filters":{"filters":{"F":{"term":{"websiteCategoryCodes":"F"}},"Y":{"term":{"websiteCategoryCodes":"Y"}},"_EDU":{"term":{"websiteCategoryCodes":"_EDU"}},"WZ":{"term":{"websiteCategoryCodes":"WZ"}},"_NF":{"term":{"websiteCategoryCodes":"_NF"}}}}}}}}}
        | existing parameters: {"term":"harry Potter","filters":{"contributors":["j. k. rowling"],"interestAge":["6-8 years"],"formatGroup":["paperback"],"publisher":["scholastic us"]},"negatives":{"salesExclusions":["GB"]}}
        | existing result: [9780439358071, 9780439064873]
        |
        */

        $parameters = $this->app->make(SearchTerms::class);

        $parameters->replace([
            'viewBy'         => 'grid',
            'resultsPerPage' => 10,
            'term'           => 'harry Potter',
            'page'           => 1,
            'contributors'   => ['j. k. rowling'],
            'interestAge'    => ['6-8 years', '13+ years'],
            'formatGroup'    => ['paperback'],
            'publisher'      => ['scholastic us'],
            'country'        => 'GB' // added from other source.
        ]);

        try {
            $parameters->validateResolved();
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        $bookSearch = new Book(new QueryBuilder(), $parameters);

        die($bookSearch->getQuery()->toJson(128));


    }
}