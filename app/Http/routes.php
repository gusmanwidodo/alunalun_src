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

$app->group(['middleware' => 'cors', 'namespace' => 'App\Http\Controllers', 'prefix' => 'file', 'as' => 'file'], function () use ($app) {
    $app->post('save[/{type}]', ['as' => 'save', 'uses' => 'FileController@saveFile']);

    $app->post('upload/{type}', ['as' => 'upload', 'uses' => 'FileController@uploadFile']);
});
