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
  static function getParent($content, $child) {
    if ($child['parent']) {
      $parent = \__::where($content, ['id' => $child['parent']]);
      return $parent ? array_search($parent[0], $content, true) : null;
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
  static function populate($source, $pure = false) {
    $content = $source;
    $nested = [];
    foreach ($source as &$s) {
      $obj = $s;
      $original = $s;
      $s = $pure ? $s : self::filter($s);
      if (is_null($original['parent'])) {
        $nested = &$s;
      } else {
        $parent = self::getParent($content, $original);
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
    return self::populate(self::getFileContent($page), true);
  }
  public function get($page) {
    try {
      return response($this->populate(self::getFileContent($page)));
    } catch (\Exception $e) {
      return response($e->getMessage(), 500);
    }
  }
  public function create(Request $request) {
    if (!empty($request->input('name'))) {
      $file = $request->input('name').self::EXT;
      $content = $request->input('content') ? $request->input('content') : '';
      if (!Storage::exists($file)) {
        Storage::put($file, '[');
        return response('Saved');
      } else {
        return response('Page already exist', 401);
      }
    } else {
      return response('You must specify a page name', 401);
    }
  }
  public function add(Request $request) {
    if (!empty($request->input('name'))) {
      $file = $request->input('name').self::EXT;
      if (Storage::exists($file)) {
        $content = $request->input('content');
        if (!empty($content)) {
          $content['id'] = uniqid();
          Storage::append($file, json_encode($content).',');
          return response('Saved');
        } else {
          return response('Cannot insert empty content', 401);
        }
      } else {
        return response('Page doesn\'t exist', 401);
      }
    } else {
      return response('You must specify a page name', 401);
    }
  }
  static public function getFileContent($page) {
    $page = "$page.json";
    if (Storage::exists($page)) {
      $content = Storage::get($page);
      return json_decode(substr_replace($content, ']', strlen($content) - 1), true);
    } else {
      throw new \Exception('File doesn\'t exist');
    }
  }
}
