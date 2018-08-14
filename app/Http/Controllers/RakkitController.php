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
  static function filter ($obj) {
    $filteredObj = [];
    $filteredObj['_id'] = $obj['id'];
    $filteredObj['_parent'] = $obj['parent'];
    $filteredObj['_title'] = $obj['title'];
    foreach($obj['fields'] as $f) {
      $filteredObj['$'.$f['name']] = $f['value'];
    }
    return $filteredObj;
  }
  static function populate($page, $pure = false) {
    $source = self::getFileContent($page);
    $content = $source;
    $nested = [];
    foreach ($source as &$s) {
      $obj = $s;
      $original = $s;
      $s = $pure ? $s : self::filter($s);
      if (is_null($original['parent'])) {
        $nested = &$s;
      } else {
        $parent = self::getParentIndex($original, $content);
        if (isset($source[$parent])) {
          if ($pure) {
            if (!isset($source[$parent]['children'])) {
              $source[$parent]['children'] = [];
            }
            $source[$parent]['children'][] = &$s;
          } else {
            $source[$parent][$obj['title']] = &$s;
          }
        }
      }
    }
    return $nested;
  }

  public function getPure($page) {
    return self::populate($page, true);
  }
  public function get($page) {
    try {
      return response($this->populate($page));
    } catch (\Exception $e) {
      return response($e->getMessage(), 500);
    }
  }
  public function create(Request $request) {
    $page = $request->input('page');
    if (!empty($page)) {
      $file = self::getPagePath($request->input('page'));
      $newElement = $request->input('new');
      if (!empty($newElement)) {
        $newElement['id'] = uniqid();
      }
      if (!Storage::exists($file)) {
        Storage::put($file, json_encode([$newElement]));
        return response('Saved');
      } else {
        if (!empty($newElement)) {
          $content = self::getFileContent($page);
          array_push($content, $newElement);
          Storage::put($file, json_encode($content));
          return response('Saved');
        }
        return response('Cannot insert empty content', 401);
      }
    }
    return response('You must specify a page name', 401);
  }
  public function update(Request $request, $page, $id) {
    $newElement = $request->input();
    $file = self::getPagePath($page);
    if (Storage::exists(self::getPagePath($page))) {
      if (!empty($newElement)) {
        $content = self::getFileContent($page);
        $index = self::getElementIndex($id, $content);
        if (isset($content[$index])) {
          $content[$index] = array_merge($content[$index], $newElement['new']);
          Storage::put($file, json_encode($content));
          return response('Saved');
        }
        return response('Element not found', 404);
      }
      return response('You must all informations (new element)', 401);
    }
    return response('Page not found', 404);
  }
  public function delete($page) {
    $page = self::getPagePath($page);
    if (Storage::exists($page)) {
      Storage::delete($page);
      return response('Deleted');
    }
    return response("Page doesn't exists", 401);
  }
}
