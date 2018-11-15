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

    // 发布类型，取决于发布正文
    private $type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $type)
    {
        $this->user = $user;
        $this->type = ucfirst($type);
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
            $func = 'get'.$this->type.'Message';
            $msg = $this->$func();
            $emailObj = InterSendMailService::getEmailInstance($to, $subject, $msg, true);
            $emailObj->sendfile();
        } catch (\Exception $e) {
            Log::info('Review email occured unexcepted, the exception: '.$e->getMessage());
            return false;
        }

        Log::info('Review email is sended successfully, please reply the fromer.');
        return true;
    }

    /**
     * 执行发单邮件审批
     *
     * @return string
     */
    private function getDeployMessage()
    {
        return <<<EOF
<html>
    <head><meta charset='utf-8'></head>
    <body>
        <h3>来自 {$this->user} 的一封项目发布审核邮件:</h3>
        <div style='padding-left:20px;padding-top:5px;font-size:1em;'>
            <strong>审批内容：</strong><a href="http://deploy.ug.edm.weibo.cn/admin/review" target='_blank'>审批项目，请及时回复!</a><br>
        </div>
    </body>
</html>
EOF;
    }

    /**
     * 执行回滚动作时，邮件提示
     *
     * @return string
     */
    private function getRollbackMessage()
    {
        return <<<EOF
<html>
    <head><meta charset='utf-8'></head>
    <body>
        <h3>来自 {$this->user} 的一封项目回滚邮件:</h3>
        <div style='padding-left:20px;padding-top:5px;font-size:1em;'>
            <strong>项目回滚，请观察回滚影响!<br>
        </div>
    </body>
</html>
EOF;
    }
}
