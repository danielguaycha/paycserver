<?php
use Illuminate\Support\Facades\Route;

// auth routes
Route::post('oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
Route::post('login', 'AuthController@login');
Route::post('logout', 'AuthController@logout');
Route::get('user', 'AuthController@user');

// clients routes
Route::get('client/search', 'Api\ClientApiController@search');
Route::apiResource('client', 'Api\ClientApiController')->except(['destroy']);

// credit routes
Route::put('credit/cancel', 'Api\CreditApiController@cancel');
Route::apiResource('credit', 'Api\CreditApiController')->except(['destroy']);
// routes
Route::apiResource('route', 'Api\RutaApiController')->only(['store', 'index']);
