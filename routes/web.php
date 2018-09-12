<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Rakkit Controller
$router->group(['prefix' => 'rakkit'], function () use ($router) {
  $router->post('/', 'ElementController@create');
  $router->delete('/{page}', 'PageController@delete');
  $router->delete('/{page}/{id}', 'ElementController@delete');
  $router->put('/{page}/{id}', 'ElementController@update');
  $router->get('/', 'PageController@getAll');
  $router->get('/variations', 'PageController@getVariations');
  $router->get('/pure/{page}', 'PageController@getPure');
  $router->get('/{page}/{variation}', 'PageController@getClean');
  $router->get('/test', 'Controller@test');
});
