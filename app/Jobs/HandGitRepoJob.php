<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;
use Symfony\Component\Process\Process;
set_time_limit(0);

class HandGitRepoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;
    protected $configId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($arrParams = null, $configId = null)
    {
        $this->params = $arrParams;
        $this->configId = $configId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->params) || empty($this->configId)) {
            Log::info('The job '.__CLASS__.' wrong params.');
            return false;
        }

        $sshAddr = $this->params['config_ssh_addr'];
        $gitUser = $this->params['config_user'];
        $branch = $this->params['config_branch'];
        $customBranch = $this->params['custom_config_branch'];
        $configId = $this->configId;
        Log::info(__LINE__.'the ssh addr: '.$sshAddr.'git user: '.$gitUser.' and the branch: '.$branch.' and the config Id: '.$configId);

        $repoPrefix = config('deployment.git_prefix');
        $srcPath = config('deployment.src_path');
        $sshArr = explode('/', $sshAddr);
        $gitName = $sshArr[count($sshArr) - 1];
        $pos = strrpos($gitName,'.');
        $gitName = substr($gitName, 0, $pos);
        Log::info('the git name: '.$gitName);
        $srcGitPath = $srcPath.'/'.$gitName;

        if (strlen($customBranch) > 0) {
            $branch = $customBranch;
        } else {
            $configArr = config('deployment.deploy_config');
            $branchArr = $configArr['task_branch'];
            $branch = $branchArr[$branch];
        }
        Log::info('the final branch: '.$branch);

        try {
            if (!file_exists($srcGitPath)) {
                $action = 'clone';
                $command = "nohup sudo -iu {$gitUser} sh -c 'sh {$srcPath}/switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"'";
                /*
                if ($gitUser == 'root') {
                    //$command = "nohup cd {$srcPath} ; sh switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"";
                    $command = "nohup sh {$srcPath}/switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"";
                } else {
//                    $command = "nohup sudo -iu {$gitUser} sh -c 'cd {$srcPath} ; sh switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"'";
                }
                */
            } else {
                $action = 'pull';
                $command = "nohup sudo -iu {$gitUser} sh -c 'sh {$srcPath}/switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"'";
                /*
                if ($gitUser == 'root') {
//                    $command = "nohup cd {$srcPath} ; sh switchBranch.sh '{$sshAddr}' '{$gitName}' '{$action}' '{$branch}' '{$configId}'";
                    $command = "nohup sh {$srcPath}/switchBranch.sh '{$sshAddr}' '{$gitName}' '{$action}' '{$branch}' '{$configId}'";
                } else {
//                    $command = "nohup sudo -iu {$gitUser} sh -c 'cd {$srcPath} ; sh switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"'";
                    $command = "nohup sudo -iu {$gitUser} sh -c 'sh {$srcPath}/switchBranch.sh \"{$sshAddr}\" \"{$gitName}\" \"{$action}\" \"{$branch}\" \"{$configId}\"'";
                }
                */
            }
            Log::info('Action: the cmd is '.$command);

            try {
                $process = new Process($command);
                $process->setTimeout(360);
                $process->start();
                $iterator = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT);
                $string = '';
                foreach ($iterator as $data) {
                    $string .= $data;
                }

                Log::info('message: '.print_r($string, true));
                // 将输出结果，记录到log中
//                    $this->getOutputTo($string);

                if (!$process->isSuccessful()) { // deploy 失败，操作失败的提示信息
                    Log::info('error: '.json_encode($process->getErrorOutput()));
                    Log::info(json_encode($command).'执行失败。');
                    return false;
                }
            } catch (\Exception $e) {
                Log::info('mage op failed, catch the exception info: '.$e->getMessage());
                return false;
            }

            Log::info('Op for deploy config is over.');
            return true;
        } catch (\Exception $e) {
            Log::info('exception info is '.$e->getMessage());
            return false;
        }
    }

    /**
     * 要处理的失败任务
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // 给用户发送失败通知，等等。。。
        Log::info('the queue is failed ,check it ,'.$exception->getMessage());
        return false;
    }
}
