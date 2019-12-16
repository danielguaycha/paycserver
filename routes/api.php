<?php
use Illuminate\Support\Facades\Route;

// auth routes
Route::post('oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
Route::post('login', 'AuthController@login');
Route::post('logout', 'AuthController@logout');
Route::post('user/password', 'AuthController@changePw');
Route::get('user', 'AuthController@user');

Route::namespace('Api')->group(function () {
    // clients routes

    Route::get('client/search', 'ClientApiController@search');
    Route::apiResource('client', 'ClientApiController')->except(['destroy']);

    // credit routes
    Route::put('credit/cancel', 'CreditApiController@cancel');
    Route::get('credit/search', 'CreditApiController@search');
    Route::put('credit/end/{id}', 'CreditApiController@finish');
    Route::apiResource('credit', 'CreditApiController')->except(['destroy']);

    // routes
    Route::apiResource('route', 'RutaApiController')->only(['store', 'index']);

    // payments
    Route::apiResource('payment', 'PaymentController');

    // expenses
    Route::apiResource('expense', 'ExpenseController');
});

