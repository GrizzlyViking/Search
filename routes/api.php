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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth:api')->group(function() {
    Route::post('test', function(\App\Http\Requests\SearchTerms $request) {

        $request->validate();
        return $request->validated();
    });

    Route::post('books', 'SearchController@index');
    Route::match(['GET', 'POST'],'category', 'SearchController@category');
    Route::match(['GET', 'POST'], 'author', 'SearchController@author');
    Route::match(['GET', 'POST'], 'publisher', 'SearchController@publisher');

    Route::match(['GET', 'POST'], 'blog', 'SearchController@blog');
    Route::match(['GET', 'POST'], 'tags', 'SearchController@tags');
});