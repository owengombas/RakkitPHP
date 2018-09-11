<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Storage;
use App\Objects\Page;
use App\Objects\Element;

ini_set('xdebug.var_display_max_depth', 100);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

global $fileVariations;
$fileVariations = new Page('.variations', true);

class ElementController extends Controller {
  static function getNewElement($newElement, $generateName = false) {
    $id = uniqid();
    $pureElement = [
      'name' => $generateName ? 'Node - '.$id : $newElement['name'],
      'fields' => $newElement['fields'],
      'parent' => $newElement['parent']
    ];
    $defaultElement = [
      'id' => $id,
      'name' => 'Unamed node',
      'parent' => NULL,
      'fields' => [],
    ];
    return array_merge($defaultElement, $pureElement);
  }
  static function isMainElement($index, $content) {
    return is_null($content[$index]['parent']);
  }
  static function getElementIndex($query, $content, $field = 'id') {
    $element = \__::where($content, is_array($query) ? $query : [$field => $query]);
    return !empty($element) ? array_search($element[0], $content, true) : null;
  }
  static function getParentIndex($child, $content) {
    if (isset($child['parent'])) {
      return self::getElementIndex($child['parent'], $content);
    }
    return null;
  }
  static function elementWithNameExists($payload, $content, $index = -1) {
    $findIndex = $index < 0;
    if ($findIndex) {
      $index = self::getElementIndex($payload['id'], $content);
    }
    $elementSameNameIndex = self::getElementIndex(['name' => $payload['name'], 'parent' => $payload['parent']], $content);
    return !is_null($elementSameNameIndex) && $index !== $elementSameNameIndex;
  }
  // Reduce the element to use it as simple as possible
  static function filter ($obj, $variation) {
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
  // Make relations (parent, children)
  static function populate($page, $pure = false, $variation = null) {
    $filePage = new Page($page);
    $source = $filePage->getContent();
    $content = $source;
    $nested = [];
    foreach ($source as &$s) {
      $original = $s;
      $s = $pure ? $s : self::filter($s, $variation);
      if (is_null($original['parent'])) {
        // The fist element name is the page name
        $s[($pure ? '' : '_').'name'] = $page;
        $nested = &$s;
      } else {
        $parent = self::getParentIndex($original, $content);
        if (isset($source[$parent])) {
          // Children property if pure mode
          if ($pure || (isset($content[$parent]['childType']) && $content[$parent]['childType'] === 'list')) {
            if (!isset($source[$parent]['items'])) {
              $source[$parent]['items'] = [];
            }
            $source[$parent]['items'][] = &$s;
          } else {
            $source[$parent][$original['name']] = &$s;
          }
        }
      }
    }
    return $nested;
  }

  public function getVariations() {
    global $fileVariations;
    return $fileVariations->content;
  }
  public function getPure($page) {
    try {
      return self::populate($page, true);
    } catch (\Exception $e) {
      return response($e->getMessage(), 500);
    }
  }
  public function get($page, $variation) {
    try {
      return $this->populate($page, false, $variation);
    } catch (\Exception $e) {
      return response($e->getMessage(), 500);
    }
  }
  public function create(Request $request) {
    $page = $request->input('page');
    if (!empty($page)) {
      $newElement = self::getNewElement($request->input('new'), true);
      $page = new Page($page);
      if ($page->exists) {
        if (!empty($newElement)) {
          $page->toMemory();
          if (!is_null(self::getParentIndex($newElement, $page->content))) {
            if (!self::elementWithNameExists($newElement, $page->content)) {
              array_push($page->content, $newElement);
              $page->writeChanges();
              return $newElement;
            }
            return response('An element with this name already exists', 403);
          }
          return response('No parent found', 403);
        }
        return response('Cannot insert empty content', 403);
      } else {
        unset($newElement['name']);
        $newElement['parent'] = NULL;
        $page->write([$newElement]);
        return $newElement;
      }
    }
    return response('You must specify a page name', 403);
  }
  // FILE
  public function update(Request $request, $page, $id) {
    $page = Element::byId($page, $id)->update($request->input());
  }
  // FILE
  public function delete($page, $id) {
    Element::byId($page, $id)->delete();
  }
}
