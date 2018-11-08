<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Mail;
use Log;
use Exception;
use Swift_RfcComplianceException;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $to;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to)
    {
        $this->to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        if ($this->attempts() > 3) {
            echo 'failed';
            return false;
        }

        $to = $this->to;

        try {
            // 发邮件
            Mail::send('mails.active', ['content' => 'this is a test Email ! by rzp !!!'], function ($m) use($to) {
                $m->from('php_net@163.com', '项目名称6666')
                    ->to($to)
                    ->subject('邮件主题-mail');
            });

            echo '成功:' . date('Ymd') . "\n";
        } catch (Swift_RfcComplianceException $e) {
            echo $e->getMessage();
            // 当任务失败时会被调用...
            Log::info($e->getMessage(), ['path' => __METHOD__, 'line' => __LINE__]);
        } catch (Exception $e) {
            echo 'ERROR';
            // 当任务失败时会被调用...
            Log::info($e->getMessage(), ['path' => __METHOD__, 'line' => __LINE__]);
        }

    }

    /**
     * 处理一个失败的任务
     *
     * @return void
     */
    public function failed()
    {
        // 当任务失败时会被调用...
        Log::info('当任务失败时会被调用', ['path' => __METHOD__, 'line' => __LINE__]);

    }
}
