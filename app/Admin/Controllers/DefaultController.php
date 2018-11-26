<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Facades\Cache;
use App\DeploymentTask;

class DefaultController extends Controller
{
    public function index(Content $content)
    {
        $loginTimes = Cache::get('loginNum');
        if (!isset($loginTimes) || empty($loginTimes))
            $loginTimes = 0;

        $data = [
            'times' => $loginTimes,
        ];
        $ret = DeploymentTask::getReviewSum();
        if (empty($ret)) {
            $data['deployNum'] = 0;
            $data['reviewNum'] = 0;
        } else {
            $data['deployNum'] = $ret['deployNum'];
            $data['reviewNum'] = $ret['reviewNum'];
        }

        return $content
            ->header('Dashboard')
            ->description('Overview')
            ->body(view('default', $data));
//        ->body(view('index'));
    }
}
