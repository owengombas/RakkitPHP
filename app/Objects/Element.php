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
  public $childType;

  private function __construct ($page, $element) {
    $this->init($page, $element);
  }
  private function init($page, $element) {
    if (!is_null($element)) {
      if (isset($element['index'])) {
        $this->index = $element['index'];
      }
      $element = $element['element'];
      if (!empty($page)) {
        $this->page = gettype($page) === 'object' ? $page : new Page($page);
      }

      $this->id = isset($element['id']) ? $element['id'] : uniqid();
      $this->parent = isset($element['parent']) ? $element['parent'] : null;
      $this->fields = isset($element['fields']) ? $element['fields'] : [];
      $this->name = isset($element['name']) ? $element['name'] : 'Node_'.$this->id;
      $this->isMain = is_null($this->parent);

      if (isset($element['childType'])) {
        $this->childType = $element['childType'];
      }
    } else {
      throw new \Exception('Cannot init a NULL Element');
    }
  }

  public function format ($main = NULL) {
    if (is_null($main)) {
      $main = $this->isMain;
    }
    $toWrite = (array)$this;
    unset($toWrite['isMain']);
    unset($toWrite['page']);
    unset($toWrite['index']);
    if ($main) {
      unset($toWrite['parent']);
      unset($toWrite['name']);
    }
    if (is_null($toWrite['childType'])) {
      unset($toWrite['childType']);
    }
    // If the page doesn't exists create an array with the element (Main Element)
    return $toWrite;
  }

  public function clean ($variation) {
    $filteredObj = [];
    $filteredObj['_id'] = $this->id;
    $filteredObj['_parent'] = $this->parent;
    if (isset($this->name)) {
      $filteredObj['_name'] = $this->name;
    }
    foreach($this->fields as $f) {
      $filteredObj['$'.$f['name']] = isset($f['variations'][$variation]) ? $f['variations'][$variation] : NULL;
    }
    return $filteredObj;
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
        return $this->format();
      } else {
        return response('Element does not exist', 403);
      }
    } else {
      return response('Page not found', 403);
    }
  }

  public function create() {
    $formated = $this->format(!$this->page->exists);
    if ($this->page->exists) {
      $this->page->toMemory();
      if ($this->parentExists()) {
        if ($this->notExistsWithName($this->name)) {
          array_push($this->page->content, $formated);
          $this->page->writeChanges();
        } else {
          return response('An element with this name already exists', 403);
        }
      } else {
        return response('No parent found', 403);
      }
    } else {
      $this->page->write([$formated]);
    }
    return $formated;
  }

  public function update($newElement) {
    if ($this->page->exists) {
      if (!empty($newElement)) {
        $newElement = Element::new($newElement);
        if ($this->notExistsWithName($newElement->name)) {
          $newElement->id = $this->id;
          if (is_null($newElement->parent) === is_null($this->parent)) {
            $this->setElement(
              array_merge(
                $this->format(),
                $newElement->format($this->isMain)
              ), false
            );
            if ($this->isMain && $newElement->name !== $this->name) {
              $this->page->rename($newElement->name);
            }
            return 'Saved';
          } else {
            return response('Cannot move the main Element', 403);
          }
        }
        return response('An element with this name already exists', 403);
      }
      return response('You must all informations (new element)', 401);
    }
    return response('Page not found', 404);
  }

  public static function new ($element, $page = NULL) {
    return new Element($page, ['element' => $element]);
  }

  public static function byId ($page, $id) {
    return self::by($page, function ($page) use ($id) {
      return $page->findById($id);
    });
  }

  public static function byQuery ($page, $query) {
    return self::by($page, function ($page) use ($query) {
      return $page->find($query);
    });
  }

  private static function by ($page, $find) {
    if (gettype($page) === 'string') {
      $page = new Page($page, true);
    }
    $element = $find($page);
    if (!is_null($element)) {
      return new Element($page, $element);
    } else {
      throw new \Exception ('Element does not exists');
    }
  }

  public function parentExists() {
    return !is_null($this->getParent());
  }

  public function getParent() {
    return $this->page->find(['id' => $this->parent]);
  }

  private function refresh() {
    return $this->init($this->page, $this->page->findById($this->id));
  }

  private function notExistsWithName($name) {
    $res = $this->page->find(['name' => $name, 'parent' => $this->parent]);
    return is_null($res) || $this->index === $res['index'];
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
