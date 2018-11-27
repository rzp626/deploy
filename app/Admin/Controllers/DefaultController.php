<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Cache;
use App\DeploymentTask;

class DefaultController extends Controller
{
    public function index(Content $content)
    {
        $loginTimes = Cache::get('loginNum');
        if (!isset($loginTimes) || empty($loginTimes))
            $loginTimes = 0;

        $addTaskNum = Cache::get('addTaskNum');
        if (!isset($addTaskNum) || empty($addTaskNum))
            $addTaskNum = 0;

        $reviewTaskNum = Cache::get('reviewTaskNum');
        if (!isset($reviewTaskNum) || empty($reviewTaskNum))
            $reviewTaskNum = 0;

        $data = [
            'times' => $loginTimes,
            'deployNum' => $addTaskNum,
            'reviewNum' => $reviewTaskNum,
        ];

//        $ret = DeploymentTask::getReviewSum();
//        if (empty($ret)) {
//            $data['deployNum'] = 0;
//            $data['reviewNum'] = 0;
//        } else {
//            $data['deployNum'] = $ret['deployNum'];
//            $data['reviewNum'] = $ret['reviewNum'];
//        }

        return $content
            ->header('Dashboard')
            ->description('Overview')
            ->body(view('default', $data));
//        ->body(view('index'));
    }
}
