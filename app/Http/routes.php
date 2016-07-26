<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return '<strong>Access Denied!</strong>';
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'image', 'as' => 'image'], function () use ($app) {
    $app->post('upload', ['as' => 'upload', 'uses' => 'FileController@postUpload']);
});
