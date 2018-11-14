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
}