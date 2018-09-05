<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Objects\Element;
use App\Objects\File;

class Controller extends BaseController {
  public function test () {
    $element = Element::byId('sdf', '5b72f73bb5b8d');
    var_dump($element);
  }
}
