<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/11/6
 * Time: 6:11 PM
 */

namespace App\Http\Controllers;

use App\Services\UtilsService;
use Illuminate\Http\Request;

class ShowPageController extends Controller
{
    public function test(Request $request)
    {
        $info = $request->get('data');
        $data = UtilsService::getFileContent($info);
        return view('deploy.index')->with('data', $data);
    }
}