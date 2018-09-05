<?php

namespace App\Objects;
use Storage;

global $EXT;
$EXT = '.json';

class Page {
  public $path;
  public $exists = false;
  public $createdByConstructor = false;
  public $content = NULL;

  function __construct ($file, $memory = false, $autoCreate = false) {
    $this->path = self::getPath($file);
    $this->exists = self::filExists($this->path);
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
    $content = json_encode($content);
    Storage::put($this->path, $content);
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
    return !empty($elements) ? ($many ? $elements : $elements[0]) : NULL;
  }
  public function exists ($id) {
    return !is_null($this->find($query, ['id' => $id]));
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
