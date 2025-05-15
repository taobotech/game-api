<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// 优先匹配 IndexController@index
$router->get('index/index', 'IndexController@index');
$router->get('index/getCategory', 'IndexController@getCategory');
$router->get('index/getGameListByCategory', 'IndexController@getGameListByCategory');
$router->get('index/searchGamesByName', 'IndexController@searchGamesByName');
// 兼容 ThinkPHP 风格的 CmsController 路由
$router->get('index/search', 'CmsController@search');
$router->get('index/detail/{id}', 'CmsController@detail');
$router->get('index/{cid}', 'CmsController@index');

// Lumen风格原有路由
$router->get('search', 'CmsController@search');
$router->get('detail/{id}', 'CmsController@detail');
$router->get('{cid}', 'CmsController@index');
