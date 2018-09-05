<?php

namespace App\Objects;
use App\Objects\Page;

class Element {
  public $id;
  public $page;
  public $name; // ?
  public $parent;
  public $fields;
  public $isMain;

  private function __construct ($page, $element) {
    if ($element) {
      $this->page = gettype($page) === 'object' ? $page : new Page($page);
      $this->isMain = is_null($element['parent']);
      $this->id = $element['id'];
      $this->fields = $element['fields'];
      $this->parent = $element['parent'];
      if (isset($element['name'])) {
        $this->name = $element['name'];
      }
    } else {
      throw new \Exception('Element is null');
    }
  }

  public function create () {
    $toWrite = (array)$this;
    unset($toWrite['isMain']);
    unset($toWrite['page']);
    // If the page doesn't exists create an array with the element (Main)
    $this->page->write($this->path->exists ? $toWrite : [$toWrite]);
  }

  public function delete () {
    if ($this->page->exists) {
      if ($this->page->exists($this->id)) {
        if ($this->isMain) {
          $page->delete();
        } else {
          function recurseDelete(&$content, $rootDeleteElementIndex) {
            // Unset childs
            foreach($content as $key => $value) {
              if ($value['parent'] === $content[$rootDeleteElementIndex]['id']) {
                recurseDelete($content, $key);
                unset($content[$key], $key);
              }
            }
            // Unset parent
            unset($content[$rootDeleteElementIndex]);
          }
          recurseDelete($page->content, $rootDeleteElementIndex);
          $page->writeChanges();
        }
        return true;
      } else {
        throw new \Exception('Element doesn\t exists');
      }
    } else {
      throw new \Exception('Page doesn\t exists');
    }
  }

  public static function new ($page, $element) {
    return new Element($page, $element);
  }

  public static function byId ($page, $id) {
    $page = new Page($page, true);
    return new Element ($page, $page->find(['id' => $id]));
  }

  public static function byQuery ($page, $query) {
    $page = new Page($page, true);
    return new Element ($page, $page->find($query));
  }
}
