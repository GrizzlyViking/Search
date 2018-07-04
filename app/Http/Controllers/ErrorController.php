<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchTerms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ErrorController extends Controller
{
    public function api()
    {
        $request = app(SearchTerms::class);

        var_dump($request->response); die();
        dd($request->messages());

        return view('errors');
    }
}
