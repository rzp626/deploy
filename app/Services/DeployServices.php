<?php

namespace App\Services;

use Log;
use Symfony\Component\Process\Process;


class DeployServices
{
    const OPT_DEPLOY = 'deploy';
    const OPT_ROLLBACK = 'releases:rollback';

    protected $releaseId = null;
    protected $action = null;
    protected $logPath = null;

    /**
     * 获取执行动作
     *
     */
    public function __construct($action)
    {
        if (!isset($action) || empty($action)) {
            Log::info('the prcess wrong action is null|'.$action);
            return false;
        }

        $this->action = $action;
    }

    /**
     * @param $processCmd
     * @return bool
     */
    public function doShellCmd($processCmd)
    {
        if (empty($processCmd) || !is_array($processCmd)) {
            Log::info('do cmd params is wrong.check it.');
            return false;
        }

        try {
            $process = new Process($processCmd);
            $process->setTimeout(360);
            $process->start();
            $iterator = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT);
            $string = '';
            foreach ($iterator as $data) {
                if (!$this->releaseId) {
                    $releaseArr = explode(" ", ltrim(trim($data, "\n")));
                    if ($this->action == self::OPT_DEPLOY) { // 部署
                        if (false !== strpos($data, "Release ID:")) {
                            $this->releaseId = !isset($releaseArr[2]) ? null : rtrim($releaseArr[2]);
                        }
                    } else if ($this->action == self::OPT_ROLLBACK) { // 回滚
                        if (false !== strpos($data, "Rollback to")) {
                            $this->releaseId = !isset($releaseArr[4]) ? null : rtrim($releaseArr[4]);
                        }
                    }
                }
                $string .= $data;
            }

            // 将输出结果，记录到log中
            $this->getOutputTo($string);

            if (!$process->isSuccessful()) { // deploy 失败，操作失败的提示信息
                Log::info('error: '.json_encode($process->getErrorOutput()));
                Log::info(json_encode($processCmd).'执行失败。');
            }

            return [
                'releaseId' => $this->releaseId,
                'logPath' => $this->logPath,
            ];
        } catch (\Exception $e) {
            Log::info('mage op failed, catch the exception info: '.$e->getMessage());
            return false;
        }

    }

    /**
     * log记录dir
     *
     * @return string
     */
    private function getOutputTo($string)
    {
        if (!$this->releaseId) {
             $this->logPath = '/data0/deploy/error/op.output';
        } else {
            $this->logPath = '/data0/deploy/opt/'.$this->action.'-releaseId-'.$this->releaseId.'.output';
        }

        file_put_contents($this->logPath, $string);
    }
}