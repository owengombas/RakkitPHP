<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Rakkit Controller
$router->group(['prefix' => 'rakkit'], function () use ($router) {
  $router->post('/element', 'RakkitController@add');
  $router->post('/', 'RakkitController@create');
  $router->get('/{page}', 'RakkitController@get');
  $router->get('/pure/{page}', 'RakkitController@getPure');
});
