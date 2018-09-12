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

class ElementController extends Controller {
  public function create(Request $request) {
    return Element::new($request->input('new'), $request->input('page'))->create();
  }
  public function update(Request $request, $page, $id) {
    return Element::byId($page, $id)->update($request->input());
  }
  public function delete($page, $id) {
    return Element::byId($page, $id)->delete();
  }
}
