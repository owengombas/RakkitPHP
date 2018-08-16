<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Rakkit Controller
$router->group(['prefix' => 'rakkit', 'middleware' => 'cors'], function () use ($router) {
  $router->post('/', 'RakkitController@create');
  $router->delete('/{page}', 'RakkitController@delete');
  $router->put('/{page}/{id}', 'RakkitController@update');
  $router->get('/', 'RakkitController@getPages');
  $router->get('/variations', 'RakkitController@getVariations');
  $router->get('/pure/{page}', 'RakkitController@getPure');
  $router->get('/{page}/{variation}', 'RakkitController@get');
});
