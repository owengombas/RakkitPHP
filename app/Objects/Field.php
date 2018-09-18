<?php

namespace App\Objects;
use Storage;
use App\Objects\Element;

class Field {
  public $name;
  public $type;
  public $variations;

  function __construct($args) {
    $this->name = isset($args['name']) ? $args['name'] : 'unamed_field';
    $this->type = isset($args['type']) ? $args['type'] : 'text:short';
    $this->variations = isset($args['variations']) ? $args['variations'] : [];
  }
}
