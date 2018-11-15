<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\CMailFileService;
use App\Services\InterSendMailService;
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
//            $emailObj = CMailFileService::getInstance($subject, $to, $from, $msg, true);
            $emailObj = InterSendMailService::getEmailInstance($to, $subject, $msg, true);
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
<html>
    <head><meta charset='utf-8'></head>
    <body>
        <h4>来自 {$this->user} 的一封审核邮件:</h4>
        <div style='padding-left:20px;padding-top:5px;font-size:1em;'>
            <strong>审批内容：</strong><a href="/http://deploy.ug.edm.weibo.cn/admin/review">审批项目，请及时回复!</a><br>
        </div>
    </body>
</html>
EOF;
    }
}
