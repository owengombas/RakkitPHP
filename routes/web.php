<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Rakkit Controller
$router->group(['prefix' => 'rakkit'], function () use ($router) {
  $router->post('/', 'RakkitController@create');
  $router->delete('/{page}', 'RakkitController@delete');
  $router->put('/{page}/{id}', 'RakkitController@update');
  $router->get('/{page}', 'RakkitController@get');
  $router->get('/pure/{page}', 'RakkitController@getPure');
});
