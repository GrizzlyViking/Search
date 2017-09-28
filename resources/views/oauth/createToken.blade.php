@extends('layouts.app')

@section('title', 'Oauth2 Clients')

@section('content')

    <div class="container">
        <passport-clients></passport-clients>
        <passport-authorized-clients></passport-authorized-clients>
        <passport-personal-access-tokens></passport-personal-access-tokens>
    </div>

@endsection
