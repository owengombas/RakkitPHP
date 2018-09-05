<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Objects\File;

class PageController extends Controller {
  public function delete($page) {
    $page = new File($page);
    if ($page->exists) {
      $page->delete();
      return 'Deleted';
    }
    return response("Page doesn't exists", 401);
  }

  public function getAll() {
    try {
      $filteredArr = array_filter(File::listFiles(), function($item) {
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
}
