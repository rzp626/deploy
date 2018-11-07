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

//Route::get('/', function () {
//    return redirect('admin');
//    return view('welcome');
//});
//Route::get('deploy/{id}','DeployController@test');

// test-router
Route::get('/', 'CasController@login');
Route::get('logout', 'CasController@logout');
Route::get('test', 'CasController@test');
Route::get('page', 'ShowPageController@test');

//Route::post('test', 'CasController@logout');