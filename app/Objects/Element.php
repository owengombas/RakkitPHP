<?php

namespace App\Objects;
use App\Objects\Page;
use App\Objects\Element;

class Element {
  public $id;
  public $page;
  public $name; // ?
  public $parent;
  public $fields;
  public $isMain;
  public $index;

  private function __construct ($page, $element) {
    $this->init($page, $element);
  }
  private function init($page, $element) {
    if ($element) {
      if (isset($element['index'])) {
        $this->index = $element['index'];
      }
      $element = $element['element'];
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

  public function toWrite () {
    $toWrite = (array)$this;
    unset($toWrite['isMain']);
    unset($toWrite['page']);
    // If the page doesn't exists create an array with the element (Main)
    $this->page->write($this->page->exists ? $toWrite : [$toWrite]);
  }

  public function delete () {
    if ($this->page->exists) {
      if ($this->page->exists($this->id)) {
        if ($this->isMain) {
          $this->page->delete();
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
          $this->refresh();
          recurseDelete($this->page->content, $this->index);
          $this->page->writeChanges();
        }
        return true;
      } else {
        throw new \Exception('Element doesn\t exists');
      }
    } else {
      throw new \Exception('Page doesn\t exists');
    }
  }

  public function a() {

  }

  // TODO: Error handling
  public function update($newElement) {
    if ($this->page->exists) {
      if (!empty($newElement)) {
        if ($this->notExistsWithName($newElement['name'])) {
          $this->refresh();
          $element = &$this->page->content[$this->index];
          $element = array_merge($element, $newElement);
          $this->page->writeChanges();
          return 'Saved';
        }
        return response('An element with this name already exists', 403);
      }
      return response('You must all informations (new element)', 401);
    }
    return response('Page not found', 404);
  }

  public static function new ($page, $element) {
    return new Element($page, ['element' => $element]);
  }

  public static function byId ($page, $id) {
    $page = new Page($page, true);
    return new Element($page, $page->findById($id));
  }

  public static function byQuery ($page, $query) {
    $page = new Page($page, true);
    return new Element($page, $page->find($query));
  }

  private function refresh() {
    return $this->init($this->page, $this->page->findById($this->id));
  }
  private function notExistsWithName($name) {
    return is_null($this->page->find(['name' => $name, 'parent' => $this->parent], true));
  }
  private function setElement($value, $refresh = true, $save = true) {
    if ($refresh) {
      $this->refresh();
    }
    $this->page->content[$this->index] = $value;
    if ($save) {
      $this->page->writeChanges();
    }
  }
}
