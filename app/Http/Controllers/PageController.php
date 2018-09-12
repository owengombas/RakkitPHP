<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Objects\Page;

global $variations;
$variations = new Page('.variations', true);

class PageController extends Controller {
  public function getAll() {
    try {
      $filteredArr = array_filter(Page::listFiles(), function($item) {
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

  public function getPure($page) {
    $page = new Page($page, true);
    return $page->getPure();
  }

  public function getClean($page, $variation) {
    $page = new Page($page, true);
    return $page->getClean($variation);
  }

  public function getVariations() {
    global $variations;
    return $variations->content;
  }
}
