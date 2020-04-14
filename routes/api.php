<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// auth routes
Route::post('oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
Route::post('login', 'AuthController@login');
Route::post('logout', 'AuthController@logout');
Route::post('user/password', 'AuthController@changePw');
Route::get('user', 'AuthController@user');

Route::namespace('Api')->group(function () {

    // employs
    Route::get('employ/info/{employId}', 'PayRollController@showInfo');
    Route::get('employ/user/{id}', 'EmployController@showByUser');
    Route::post('employ/ruta', 'EmployController@store_ruta');
    Route::put('employ/cancel/{id}', 'EmployController@cancel');
    Route::apiResource('employ', 'EmployController');


    // clients routes
    Route::get('client/search', 'ClientController@search');
    Route::put('client/cancel/{id}', 'ClientController@cancel');
    Route::put('client/mora/{id}', 'ClientController@mora');
    Route::apiResource('client', 'ClientController');

    // credit routes
    Route::put('credit/cancel/{id}', 'CreditController@cancel');
    //Route::get('credit/search', 'CreditController@search');
    Route::put('credit/end/{id}', 'CreditController@finish');
    Route::apiResource('credit', 'CreditController')->except(['destroy']);

    // routes
    Route::apiResource('route', 'RutaController');

    // payments
    Route::get('payment/only/{id}', 'PaymentController@showByCredit');
    Route::apiResource('payment', 'PaymentController')->only(['index', 'show', 'update', 'destroy']);
    // expenses
    Route::get('/expense/info', 'ExpenseController@info');
    Route::apiResource('expense', 'ExpenseController')->except(['update']);

    // payroll
    Route::apiResource('payroll', 'PayRollController');


    // prueba
    Route::get('prueba', 'CreditController@calcDate');
});

Route::get('image/{path}/{filename}', 'AdminController@viewImg');
