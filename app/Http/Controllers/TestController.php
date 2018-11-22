<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/11/8
 * Time: 3:51 PM
 */

namespace App\Http\Controllers;

use App\Jobs\SendMailJob;
use  App\Http\Controllers\Controller;
use Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Log;

class TestController extends Controller
{
    /**
     * 邮件测试
     */
    public function mail()
    {
        $toArr = [
            'php_net@163.com',
        ];

        foreach ($toArr as $to) {
            $job = (new SendMailJob($to));
            $this->dispatch($job);
        }

        echo 'success';

//        $to = 'php_net@163.com';
//        $subject = '邮件名称';
//        Mail::send(
//            'mails.active',
//            ['content' => $message],
//            function ($message) use ($to, $subject) {
//                $message->to($to)->subject($subject);
//            }
//        );
    }

    /**
     * 是否需要白名单过滤
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function redisInfo(Request $request)
    {
        $data = [
            'code' => 4000,
            'msg'  => 'Wrong params, please check it.',
        ];

        if (!$request->isMethod('post')) {
            $data['code'] = 4001;
            $data['msg'] = 'Wrong request method, it should be post.';
        }

        $redisKey = $request->get('categoryKey');
        $categoryName = $request->get('categoryName');
        $midStr = $request->get('mid');
//        $midArr = json_decode($midStr, true);
        if (!isset($redisKey) || empty($redisKey) || !isset($categoryName) || empty($categoryName) || !isset($midStr) || empty($midStr)) {
            return response()->json($data);
        }

        $isExistedKey = Redis::exists($redisKey);
        if ($isExistedKey) {
            $data['code'] = 4002;
            $data['msg'] = 'Existed Key, check the key!';
            return response()->json($data);
        }

        try {
            // 添加分类名称
            Redis::set($redisKey, $categoryName);

            // 添加分类id
            $hashKey = 'channel_ad_material_hot_feed_materials';
            Redis::hset($hashKey, $redisKey, $midStr);
        } catch(\Exception $e) {
            Log::info('add key failed or add mids failed');
            $data['code'] = 4003;
            $data['msg'] = 'Failed to add the new key, check it!';
            return response()->json($data);
        }

        $data['code'] = 2000;
        $data['msg'] = 'Successfully add the new key.';
        return response()->json($data);
    }
}