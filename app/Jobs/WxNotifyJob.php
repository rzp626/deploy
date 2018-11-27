<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;
use App\Services\UtilsService;
use App\DeploymentTask;

class WxNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $requestUser;
    private $responseUser;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requestUser, $responseUserId)
    {
        $this->requestUser = $requestUser;
        if (empty($requestUser) || empty($responseUserId)) {
            Log::info('Notify the user by wx failed, check the params.');
        }

        $userInfo = DeploymentTask::getUserInfo();
        Log::info('the user info '.json_encode($userInfo));
        $userName = $userInfo[$responseUserId];
        if (!isset($userName) || empty($userName)) {
            $userName = 'zhenpeng8';
            Log::info('The review user is wrong, please check the params');
        }

        $this->responseUser = $userName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('message: the request user is '.$this->requestUser . ' and the response user is '.$this->responseUser);
        if (empty($this->requestUser) || empty($this->responseUser)) {
            return false;
        }

        $params = config('params.wx_params');
        Log::info('the info is '. json_encode($params));

        $receiveMsg = 'Hi '.$this->responseUser.', '.$this->requestUser. '在'. date('Y-m-d H:i:s', time()).'发起了上线发单操作，请及时审核。';
        Log::info('Receive msg: '. $receiveMsg);

        $sendMsg = $this->requestUser.', 你在'.date('Y-m-d H:i:s', time()).'执行了上线发单操作!' ;
        Log::info('Request msg: '. $sendMsg);

        $url = $params['schema'].$params['host'].':'.$params['port'].$params['uri'];
        $sendMsgUrl = $url.$this->requestUser . '/' .urlencode($sendMsg);
        $ret = UtilsService::curl($sendMsgUrl);
        if (!isset($ret) || empty($ret)) {
            Log::info('Notify the send user failed, by the wx. U can check it. The url is '.$sendMsgUrl);
        }

        $receiveMsgUrl = $url.$this->responseUser . '/' . urlencode($receiveMsg);
        $ret = UtilsService::curl($receiveMsgUrl);
        if (!isset($ret) || empty($ret)) {
            Log::info('Notify the receive user failed, by the wx. U can check it. The url is '. $receiveMsgUrl);
        }
    }
}
