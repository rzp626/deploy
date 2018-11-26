<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Facades\Cache;

class DefaultController extends Controller
{
    public function index(Content $content)
    {
        $loginTimes = Cache::get('loginNum') || 0;
        $data = [
            'times' => $loginTimes,
        ];

        return $content
            ->header('Dashboard')
            ->description('Overview')
            ->body(view('default', $data));
//        ->body(view('index'));
    }
}
