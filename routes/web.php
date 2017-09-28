<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('search')->group(function() {
    Route::get('clients', function () {

        return view('oauth.createToken');
    });
});

/*
|--------------------------------------------------------------------------
| OAUTH2
|--------------------------------------------------------------------------
|
| In order to get a token, which in turn can be used to authenticate a request
| via the header. You need to progress through the following steps.
|
|   1. create the client in [web]/clients
|       * which provides you with a client id, secret and redirect uri
|   2. using this the consuming client, which would get code, which in term would be provided
|      to the callback endpoint, which from the callback endpoint will call our api you would
|      the token as json.
|
*/
Route::middleware('auth')->group(function () {

    Route::get('redirect', function () {
        $query = http_build_query([
            'client_id'     => 4,
            'redirect_uri'  => 'http://search.seb/callback',
            'response_type' => 'code',
            'scope'         => '',
        ]);

        return redirect(env('APP_URL') . '/oauth/authorize?' . $query);
    });

    Route::get('/callback', function (Request $request) {
        $http = new GuzzleHttp\Client;

        $response = $http->post(env('APP_URL') . '/oauth/token', [
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'client_id'     => 4,
                'client_secret' => 'feZoJxpgaE8cQenexCXOtIdzk4b7qahLUI7yC2Lm',
                'redirect_uri'  => 'http://search.seb/callback',
                'code'          => $request->code,
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    });

    //Route::get('users', 'UserController@index');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');