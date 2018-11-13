<?php

namespace App\Services;

use Log;
use Symfony\Component\Process\Process;


class DeployServices
{
    const OPT_DEPLOY = 'deploy';
    const OPT_ROLLBACK = 'releases:rollback';

    protected static $releaseId = null;
    protected static $action = null;
    protected static $logPath = null;
    /**
     * @param $processCmd
     * @return bool
     */
    public static function doShellCmd($processCmd)
    {
        $process = new Process($processCmd);
        $action = $processCmd[3];
        if (!isset($action) || empty($action)) {
            Log::info('the prcess wrong action is null|'.$action);
            return false;
        }
        self::$action = $action;
        $process->setTimeout(360);
        $process->start();
        $iterator = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT);
        $string = '';
        foreach ($iterator as $data) {
            if (!self::$releaseId) {
                $releaseArr = explode(" ", ltrim(trim($data, "\n")));
                if ($action == self::OPT_DEPLOY) { // 部署
                    if (false !== strpos($data, "Release ID:")) {
                        self::$releaseId = !isset($releaseArr[2]) ? null : rtrim($releaseArr[2]);
                    }
                } else if ($action == self::OPT_ROLLBACK) { // 回滚
                    if (false !== strpos($data, "Rollback to")) {
                        self::$releaseId = !isset($releaseArr[4]) ? null : rtrim($releaseArr[4]);
                    }
                }
            }
            $string .= $data;
        }

        // 将输出结果，记录到log中
        self::getOutputTo($string);

        if (!$process->isSuccessful()) { // deploy 失败，操作失败的提示信息
            Log::info('error: '.json_encode($process->getErrorOutput()));
            Log::info(json_encode($processCmd).'执行失败。');
        }

        return [
            'releaseId' => self::$releaseId,
            'logPath' => self::$logPath,
        ];
    }

    /**
     * log记录dir
     *
     * @return string
     */
    protected static function getOutputTo($string)
    {
        if (!self::$releaseId) {
             self::$logPath = '/data0/deploy/error/op.output';
        } else {
            self::$logPath = '/data0/deploy/opt/'.self::$action.'-releaseId-'.self::$releaseId.'.output';
        }

        file_put_contents(self::$logPath, $string);
    }
}