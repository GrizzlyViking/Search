<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('api')->group(function() {
    Route::post('{countyCode}/books', 'SearchController@index');
    Route::get('{countyCode}/books', 'SearchController@index');
    Route::get( '{countryCode}/category/{category}', 'SearchController@category');
    Route::match(['GET', 'POST'], 'author/{author}', 'SearchController@author');
    Route::match(['GET', 'POST'], 'publisher/{publisher}', 'SearchController@publisher');
    Route::match(['GET', 'POST'], 'series/{series}', 'SearchController@series');

    Route::match(['GET', 'POST'], 'blog', 'BlogController@index');
    Route::get('testParameters', 'SearchController@testParameters');

    Route::match(['POST', 'GET', 'PUT'], 'errors', ['as' => 'errors', 'uses' => function(){

        return view('errors');
    }]);
});

