<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Rakkit Controller
$router->group(['prefix' => 'rakkit'], function () use ($router) {
  $router->post('/', 'RakkitController@create');
  $router->delete('/{page}', 'RakkitController@deletePage');
  $router->delete('/{page}/{id}', 'RakkitController@deleteElement');
  $router->put('/{page}/{id}', 'RakkitController@update');
  $router->get('/', 'RakkitController@getPages');
  $router->get('/variations', 'RakkitController@getVariations');
  $router->get('/pure/{page}', 'RakkitController@getPure');
  $router->get('/{page}/{variation}', 'RakkitController@get');
});
