# Elastic Search
This is intended to be the micro service for Book Search. But it will also be able to do searches on other Elastic Indexes, ex. blogs, tags.

## Endpoints
```php
+---------------+----------------------+----------------------------------------------------------------------------+------------+
| Method        | URI                  | Action                                                                     | Middleware |
+---------------+----------------------+----------------------------------------------------------------------------+------------+
| GET|POST|HEAD | author               | App\Http\Controllers\SearchController@author                               | api        |
| GET|POST|HEAD | blog                 | App\Http\Controllers\SearchController@blog                                 | api        |
| POST          | books                | App\Http\Controllers\SearchController@index                                | api        |
| GET|POST|HEAD | category             | App\Http\Controllers\SearchController@category                             | api        |
| GET|POST|HEAD | publisher            | App\Http\Controllers\SearchController@publisher                            | api        |
| GET|POST|HEAD | tags                 | App\Http\Controllers\SearchController@tags                                 | api        |
+---------------+----------------------+----------------------------------------------------------------------------+------------+
```

#### ElasticSearchBuilder Query
```json
{
    "_source": [
        "isbn13"
    ],
    "query": {
        "function_score": {
            "query": {
                "bool": {
                    "must": [
                        {
                            "multi_match": {
                                "query": "Harry Potter",
                                "type": "cross_fields",
                                "operator": "and",
                                "analyzer": "english_std_analyzer",
                                "fields": [
                                    "boostedFullText.english_no_tf^7",
                                    "fullText.english_no_tf^2"
                                ]
                            }
                        }
                    ],
                    "should": [
                        {
                            "multi_match": {
                                "fields": [
                                    "boostedFullText.unstemmed_no_tf^7",
                                    "fullText.unstemmed_no_tf^2"
                                ],
                                "operator": "OR",
                                "type": "cross_fields",
                                "analyzer": "unstemmed",
                                "query": "Harry Potter"
                            }
                        },
                        {
                            "multi_match": {
                                "fields": [
                                    "boostedFullText.english^7",
                                    "fullText.english^2"
                                ],
                                "operator": "OR",
                                "type": "phrase",
                                "analyzer": "english_std_analyzer",
                                "query": "Harry Potter"
                            }
                        }
                    ],
                    "must_not": [
                        {
                            "terms": {
                                "salesExclusions": [
                                    "GB"
                                ]
                            }
                        }
                    ]
                }
            },
            "functions": [
                {
                    "script_score": {
                        "script": "(1 + Math.pow(_score, 0.5)  * doc['scores.inStock'].value * (\n                    0.25 * doc['scores.sales30ALL'].value + \n                    0.1 * doc['scores.sales90ALL'].value + \n                    0.005 * doc['scores.sales180ALL'].value + \n                    0.05 * doc['scores.leadTime'].value + \n                    0.15 * doc['scores.readyToGo'].value + \n                    0.01 * doc['scores.hasJacket'].value + \n                    0.01 * doc['scores.hasGallery'].value  \n                    ))"
                    }
                }
            ],
            "score_mode": "first",
            "boost_mode": "replace"
        }
    },
    "size": 20,
    "aggregations": {
        "Express Delivery": {
            "filter": {
                "term": {
                    "forSale": 1
                }
            },
            "aggregations": {
                "Express Delivery": {
                    "terms": {
                        "field": "leadTime"
                    }
                }
            }
        },
        "author": {
            "terms": {
                "field": "contributors.exact_matches_ci"
            }
        },
        "Age Group": {
            "range": {
                "field": "interestAge",
                "keyed": true,
                "ranges": [
                    {
                        "key": "Babies",
                        "to": 2
                    },
                    {
                        "key": "Toddlers",
                        "to": 3,
                        "from": 1
                    },
                    {
                        "key": "3-5 years",
                        "to": 6,
                        "from": 3
                    },
                    {
                        "key": "6-8 years",
                        "to": 9,
                        "from": 6
                    },
                    {
                        "key": "9-12 years",
                        "to": 13,
                        "from": 9
                    },
                    {
                        "key": "13+ years",
                        "from": 13
                    }
                ]
            }
        },
        "Publication Date": {
            "range": {
                "field": "publicationDate",
                "keyed": true,
                "ranges": [
                    {
                        "key": "Coming soon",
                        "to": "2018-02-14",
                        "from": "2017-11-14"
                    },
                    {
                        "key": "Within the last month",
                        "to": "2017-11-14",
                        "from": "2017-10-14"
                    },
                    {
                        "key": "Within the last 3 months",
                        "to": "2017-11-14",
                        "from": "2017-08-14"
                    },
                    {
                        "key": "Within the last year",
                        "to": "2017-11-14",
                        "from": "2016-11-14"
                    },
                    {
                        "key": "Over a year ago",
                        "to": "2016-11-14"
                    }
                ]
            }
        },
        "formats": {
            "terms": {
                "field": "formatGroup.exact_matches_ci"
            }
        },
        "languages": {
            "terms": {
                "field": "languages"
            }
        },
        "series": {
            "terms": {
                "field": "series.exact_matches_ci"
            }
        },
        "publisher": {
            "terms": {
                "field": "publisher.exact_matches_ci"
            }
        },
        "rating": {
            "range": {
                "field": "averageRating",
                "keyed": true,
                "ranges": [
                    {
                        "key": "1 star",
                        "from": 0.01
                    },
                    {
                        "key": "2 stars",
                        "from": 1.5
                    },
                    {
                        "key": "3 stars",
                        "from": 2.5
                    },
                    {
                        "key": "4 stars",
                        "from": 3.5
                    },
                    {
                        "key": "5 stars",
                        "from": 4.5
                    }
                ]
            }
        },
        "websiteCategoryCodes": {
            "filters": {
                "filters": {
                    "F": {
                        "term": {
                            "websiteCategoryCodes": "F"
                        }
                    },
                    "Y": {
                        "term": {
                            "websiteCategoryCodes": "Y"
                        }
                    },
                    "WZ": {
                        "term": {
                            "websiteCategoryCodes": "WZ"
                        }
                    },
                    "_EDU": {
                        "term": {
                            "websiteCategoryCodes": "_EDU"
                        }
                    },
                    "_NF": {
                        "term": {
                            "websiteCategoryCodes": "_NF"
                        }
                    }
                }
            }
        }
    }
}
```