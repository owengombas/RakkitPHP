<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Storage;

class RakkitController extends Controller {
  const EXT = '.json';
  public function __construct() {}
  public function get($page) {
    try {
      global $content;
      $content = json_decode(self::getFileContent($page), true);
      function getParent($child) {
        global $content;
        return \__::where($content, ['parent' => $child['parent']]);
      }
      function filter ($obj) {
        $filteredObj = [];
        $filteredObj['_id'] = $obj['id'];
        $filteredObj['_parent'] = $obj['parent'];
        $filteredObj['_title'] = $obj['title'];
        return $filteredObj;
      }
      function populate ($level) {
        global $newObj;
        $newObj = [];
        function searchParent($element) {
          if ($element['parent']) {
            $parent = getParent($c);
            searchParent($parent);
            
          } else {
            return null;
          }
        }
        function deep($el) {
          global $newObj;
          foreach($level as $c) {
            $child = filter($c);
            if ($parent) {
              $parent = $parent[0];
              $newObj += [$parent['title'] => []];
              $newObj[$parent['title']] += [$c['title'] => $child];
              var_dump($parent);
              deep([$parent]);
            } else {
              $newObj += [$c['title'] => $child];
            }
          }
        }
        return $newObj;
      }
      var_dump(populate($content));
      return response($content);
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
      return substr_replace($content, ']', strlen($content) - 1) ;
    } else {
      throw new \Exception('File doesn\'t exist');
    }
  }
}
