<?php
use \Illuminate\Support\Facades\Route;


Route::get('/{vue_capture?}', function () {
    return view('main');
})->where('vue_capture', '[\/\w\.-]*');

/*
Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');
// empleados
Route::put('employ/{id}/cancel', 'EmployController@cancel')->name('employ.cancel');
Route::get('employ/ruta', 'EmployController@assign_ruta')->name('employ.assign_route');
Route::post('employ/ruta', 'EmployController@store_ruta')->name('employ.store_route');
Route::resource('employ', 'EmployController');
// rutas
Route::resource('ruta', 'RutaController');
// roles
Route::resource('rol', 'RoleController');


Route::get('storage/{pathFile}/{filename}', 'AdminController@viewImg');
*/
