<?php

namespace App\Objects;
use Storage;
use App\Objects\Element;

global $EXT;
$EXT = '.json';

class Page {
  public $path;
  public $name;
  public $exists = false;
  public $createdByConstructor = false;
  public $content = NULL;

  function __construct ($file, $memory = false, $autoCreate = false) {
    $this->name = $file;
    $this->path = self::getPath($file);
    $this->exists = self::fileExists($this->path);
    if (!$this->exists && $autoCreate) {
      $this->write();
      $this->createdByConstructor = true;
    }
    if ($memory) {
      if ($this->exists) {
        $this->toMemory();
      } else {
        throw new \Exception('File does not exists');
      }
    }
  }

  public function clearMemory () {
    $this->content = NULL;
  }
  public function toMemory () {
    $this->content = $this->getContent();
    return $this->content;
  }
  public function delete () {
    Storage::delete($this->path);
    $this->exists = false;
  }
  public function getContent () {
    return isset($this->content) ? $this->content : json_decode(Storage::get($this->path), true);
  }
  public function write ($content = '') {
    Storage::put($this->path, json_encode($content));
    $this->content = $content;
    $this->exists = true;
  }
  public function writeChanges () {
    $this->write($this->content);
  }
  public function create () {
    $this->write();
  }
  public function find($query, $many = false) {
    $elements = \__::where($this->content, $query);
    return !empty($elements) ? ($many ? $elements : ['element' => $elements[0], 'index' => array_search($elements[0], $this->content, true)]) : NULL;
  }
  public function findById($id) {
    return $this->find(['id' => $id]);
  }
  public function findByName($name) {
    return $this->find(['name' => $name], true);
  }
  public function exists ($id) {
    return !is_null($this->findById($id));
  }
  public function getClean($variation) {
    return $this->populate(false, $variation);
  }
  public function getPure() {
    return $this->populate(true);
  }
  public function rename($name) {
    Storage::move($this->path, self::getPath($name));
  }

  // Make relations (parent, children)
  private function populate($pure = false, $variation = null) {
    $source = $this->getContent();
    $content = $source;
    $nested = [];
    foreach ($source as &$original) {
      $element = Element::new($original, $this);
      $original = $pure ? $element->format() : $element->clean($variation);
      if ($element->isMain) {
        // The fist element name is the page name
        $original[($pure ? '' : '_').'name'] = $this->name;
        $nested = &$original;
      } else {
        $parent = $element->getParent();
        if (isset($parent)) {
          $parent = Element::byId($this, $parent['element']['id']);
          // Children property if pure mode
          if ($pure || (isset($parent->childType) && $parent->childType === 'list')) {
            if (!isset($source[$parent->index]['items'])) {
              $source[$parent->index]['items'] = [];
            }
            $source[$parent->index]['items'][] = &$original;
            // Sort Here for editing usage
          } else {
            $source[$parent->index][$element->name] = &$original;
          }
        }
      }
    }
    return $nested;
  }

  // Reduce the element to use it as simple as possible
  private function filter ($obj, $variation) {
    $filteredObj = [];
    $filteredObj['_id'] = $obj['id'];
    $filteredObj['_parent'] = $obj['parent'];
    if (isset($obj['name'])) {
      $filteredObj['_name'] = $obj['name'];
    }
    foreach($obj['fields'] as $f) {
      $filteredObj['$'.$f['name']] = isset($f['variations'][$variation]) ? $f['variations'][$variation] : NULL;
    }
    return $filteredObj;
  }

  public static function listFiles () {
    return Storage::disk('local')->files();
  }
  private static function getPath ($file) {
    global $EXT;
    return $file.$EXT;
  }
  private static function fileExists ($path) {
    return Storage::exists($path);
  }
}
