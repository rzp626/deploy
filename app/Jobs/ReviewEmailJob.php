<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\CMailFileService;
use Log;

class ReviewEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 发件代理
    const SEND_MAIL_M = 'dau_monitor@vip.sina.com';

    // 发件人
    private $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->user)) {
            Log::info('Review email params were wrong. the user or the subject is empty, please check it.');
        }
        try {
            // 收件人
            $emailArr = config('review.email');
            $to = implode(',', $emailArr);
            $from = self::SEND_MAIL_M;
            $subject = 'UG上线平台，发单审批';
            $msg = $this->getMessage();
            $emailObj = CMailFileService::getInstance($subject, $to, $from, $msg, true);
            $emailObj->sendfile();
        } catch (\Exception $e) {
            Log::info('Review email occured unexcepted, the exception: '.$e->getMessage());
            return false;
        }

        Log::info('Review email is sended successfully, please reply the fromer.');
        return true;
    }

    private function getMessage()
    {
        return <<<EOF
'{$this->user}'给你发了封发单审核邮件，请第一时间回复下。<br>
项目所在的路径：<br>
&nbsp;&nbsp;&nbsp;&nbsp;http://http://deploy.ug.edm.weibo.cn/admin/review<br>
或者是a连接
EOF;
    }
}
