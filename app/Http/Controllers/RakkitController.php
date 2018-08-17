<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Storage;

ini_set('xdebug.var_display_max_depth', 100);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

class RakkitController extends Controller {
  const EXT = '.json';

  public function __construct() {}

  static function exists($page) {
    return Storage::exists(self::getPagePath($page));
  }
  static function updateFile($page, $content) {
    Storage::put(self::getPagePath($page), json_encode($content));
  }
  static function deleteFile($page) {
    Storage::delete(self::getPagePath($page));
  }
  static function getNewElement($newElement) {
    $defaultElement = [
      'id' => uniqid(),
      'name' => 'Unamed element',
      'parent' => NULL,
      'fields' => [],
    ];
    return array_merge($defaultElement, $newElement);
  }
  static function getPagePath($page) {
    return $page.self::EXT;
  }
  static public function getFileContent($page) {
    $page = self::getPagePath($page);
    if (Storage::exists($page)) {
      $content = Storage::get($page);
      return json_decode($content, true);
    } else {
      throw new \Exception('File doesn\'t exist');
    }
  }
  static public function getVariationsList() {
    return self::getFileContent('.variations');
  }
  static function getElementIndex($id, $content) {
    $element = \__::where($content, ['id' => $id]);
    return !empty($element) ? array_search($element[0], $content, true) : null;
  }
  static function getParentIndex($child, $content) {
    if ($child['parent']) {
      return self::getElementIndex($child['parent'], $content);
    }
    return null;
  }
  // Reduce the element to use it as simple as possible
  static function filter ($obj, $variation) {
    $filteredObj = [];
    $filteredObj['_id'] = $obj['id'];
    $filteredObj['_parent'] = $obj['parent'];
    $filteredObj['_name'] = $obj['name'];
    foreach($obj['fields'] as $f) {
      $filteredObj['$'.$f['name']] = isset($f['variations'][$variation]) ? $f['variations'][$variation] : NULL;
    }
    return $filteredObj;
  }
  // Make relations (parent, children)
  static function populate($page, $pure = false, $variation = null) {
    $source = self::getFileContent($page);
    $content = $source;
    $nested = [];
    foreach ($source as &$s) {
      $original = $s;
      $s = $pure ? $s : self::filter($s, $variation);
      if (is_null($original['parent'])) {
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
  public function getVariations() {
    return self::getVariationsList();
  }
  public function getPages() {
    try {
      $filteredArr = array_filter(Storage::disk('local')->files(), function($item) {
        return $item[0] !== '.';
      });
      $filteredArr = array_map(function($item) {
        return pathinfo($item, PATHINFO_FILENAME);
      }, $filteredArr);
      return array_values($filteredArr);
    } catch (\Exception $e) {
      return response($e->getMessage(), 500);
    }
  }
  public function create(Request $request) {
    $page = $request->input('page');
    if (!empty($page)) {
      $newElement = self::getNewElement($request->input('new'));
      if (self::exists($page)) {
        self::updateFile($page, [$newElement]);
        return 'Saved';
      } else {
        if (!empty($newElement)) {
          $content = self::getFileContent($page);
          array_push($content, $newElement);
          self::updateFile($page, $content);
          return 'Saved';
        }
        return response('Cannot insert empty content', 401);
      }
    }
    return response('You must specify a page name', 401);
  }
  public function update(Request $request, $page, $id) {
    $newElement = $request->input();
    if (self::exists($page)) {
      if (!empty($newElement)) {
        $content = self::getFileContent($page);
        $index = self::getElementIndex($id, $content);
        if (isset($content[$index])) {
          $content[$index] = array_merge($content[$index], $newElement);
          self::updateFile($page, $content);
          return 'Saved';
        }
        return response('Element not found', 404);
      }
      return response('You must all informations (new element)', 401);
    }
    return response('Page not found', 404);
  }
  public function deletePage($page) {
    if (self::exists($page)) {
      self::deleteFile($page);
      return 'Deleted';
    }
    return response("Page doesn't exists", 401);
  }
  public function deleteElement($page, $id) {
    if (self::exists($page)) {
      $content = self::getFileContent($page);
      $parentIndex = self::getElementIndex($id, $content);
      function recurseDelete(&$content, $parentIndex) {
        // Unset childs
        foreach($content as $key => $value) {
          if ($value['parent'] === $content[$parentIndex]['id']) {
            recurseDelete($content, $key);
            unset($content[$key], $key);
          }
        }
        // Unset parent
        unset($content[$parentIndex]);
      }
      recurseDelete($content, $parentIndex);
      self::updateFile($page, $content);
      return 'Deleted';
    }
    return response("Page doesn't exists", 401);
  }
}
