<?php

namespace App\Jobs;

use App\Services\DeployServices;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;
use DB;
use App\DeploymentTask;
use App\DeploymentConfig;
use Exception;
use Mage\MageApplication;
set_time_limit(0);

class DeployOptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 部署动作
     *
     * @var string
     */
    private $action;

    /**
     * 执行参php artisan queue:restart数
     *
     * @var array
     */
    private $params;

    /**
     * 修改发布状态
     *
     * @var bool
     */
    private $taskStatus = false;

    /**
     * 修改回滚状态
     *
     * @var bool
     */
    private $releaseStatus = false;

    private $config_hosts;

    private $config_hosts_path;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($action, $params)
    {
        $this->action = $action;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('the action: '.$this->action.', and the params: '.json_encode($this->params));
        if (!isset($this->action) || empty($this->params)) {
            return false;
        }

        switch ($this->action)  {
            case 'deploy':
                $this->taskStatus = true;
                $this->optDeploy();
                break;
            case 'releases:rollback':
                $this->releaseStatus = true;
                $this->optRollback();
                break;
            default:
                Log::info('the action('.$this->action.') is wrong, check it.');
                break;
        }

        return true;
    }

    /**
     * 处理共同部分
     *
     * @return void
     */
    protected function optCommon()
    {
        $taskBranchArr = config('deployment.deploy_config.task_branch');
        $taskEnvArr = config('deployment.deploy_config.task_env');

        $env_name = $taskEnvArr[$this->params['envId']];
        $branch_name = $taskBranchArr[$this->params['branchId']];
        $info = DeploymentConfig::where('id', $this->params['configId'])->first();
        $config_path = $info->config_from;
        $config_name = $info->config_name;
        $config_env = $info->config_env;
        $sshAddr = $info->config_ssh_addr;
        $config_branch = $info->config_branch;
        $this->config_hosts = $info->config_hosts; // 远程主机地址
        $this->config_hosts_path = rtrim($info->config_host_path, '/'); // 远程主机目录

        $vendorMageBin = base_path().'/vendor/bin/mage';
        if (!file_exists($vendorMageBin)) {
            echo "magephp可执行文件，未发现，请检查.";
            exit;
        }

        // 切换到部署项目所在目录
        $sshArr = explode('/', $sshAddr);
        $gitName = $sshArr[count($sshArr) - 1];
        $pos = strrpos($gitName,'.');
        $gitName = substr($gitName, 0, $pos);
        $config_path = rtrim(config('deployment.src_path'), '/').'/'.$gitName;
        chdir($config_path);
        Log::info('now the path== '.getcwd());
        // 测试环境下的php路径
        $phpPathArr = config('deployment.php_path');
        Log::info('the php path '.print_r($phpPathArr, true));
        $phpPath = $phpPathArr['test']; // 本地
//        $phpPath = $phpPathArr['production']; // 线上

        $ymlPath = rtrim(config('deployment.yml_path'), '/').'/config-'.$this->params['configId'].'-mage.yml';
        $cmd = ['nohup', $phpPath, $vendorMageBin, $this->action, $env_name, $ymlPath];
//        $cmd = ['nohup', $phpPath, $vendorMageBin, $this->action, $env_name];
        Log::info('the cmd info: '.json_encode($cmd));

        return $cmd;
    }

    /**
     * 修改记录状态值
     *
     * @return void
     */
    protected function modifyStatus($status, $condition = null)
    {
        // 对releaseId，判断是否为null
        Log::info('modify status: '.$status. ' and condition is: '.json_encode($condition));
        // 后面加上sql语句执行的log
        DB::enableQueryLog();
        $deployModel = DeploymentTask::find($this->params['taskId']);
        $deployModel->released_at = date("Y-m-d H:i:s", time());
        if (!empty($condition) || !empty($condition['releaseId'])) {
            $deployModel->release_id = $condition['releaseId'];
        }

        if ($this->taskStatus) {
            $deployModel->task_status = $status;
        }

        if ($this->releaseStatus) {
            $deployModel->release_status = $status;
        }

        $deployModel->save();
        Log::info(DB::getQueryLog());
    }

    /**
     * 清空opcache
     *
     * @return void
     */
    protected function clearCache()
    {
        // 分别清除各个主机opcache
        $dirArr = explode('|', $this->config_hosts);
        foreach ($dirArr as $dir) {
            if (false !== strpos($dir, ':')) {
                $realDir = explode(":", $dir);
                $path = $realDir[0];
            } else {
                $path = $dir;
            }

            // cli参数现在是写死的。。。
            $cmd = "ssh root@$path -p 26 'cd {$this->config_hosts_path}/current;/usr/local/sina_mobile/php7/bin/php cachetool opcache:reset --fcgi=127.0.0.1:9000'";
            Log::info('the rollback opcache cmd: '.$cmd);
            exec($cmd, $output, $result);
            if ((int)$result !== 0) {
                Log::info('the '.$dir.' clear opcache failed. the outputis'.json_encode($output).', the return value is '.$result);
            } else {
                Log::info('Rollback to clear opcache successfully, the return value is '.$result);
            }
        }
    }

    /**
     * 处理发布动作
     *
     * @return void
     */
    protected function optDeploy()
    {
        $arrCmd = $this->optCommon();
        if (!isset($arrCmd) || empty($arrCmd) || count($arrCmd) !== 6) {
            Log::info('the wrong cmd is: '.json_encode($arrCmd));
            return false;
        }

        $deployObj = new DeployServices($this->action);
        $res = $deployObj->doShellCmd($arrCmd);
        if (false === $res) {
            Log::info('the action: '.$this->action.' deploy failed.check it.');
            $status = 1;
            $this->modifyStatus($status);
            return false;
        } else if (is_array($res)) {
            Log::info('the acton: '.$this->action.' & the res is: '.json_encode($res));
            if (!isset($res['releaseId']) || !isset($res['logPath'])) {
                $status = 3;
            } else {
                $status = 2;
            }

            $this->modifyStatus($status, $res);
        }


        return true;
    }

    /**
     * 处理回滚操作
     *
     * @return void
     */
    protected function optRollback()
    {
        $arrCmd = $this->optCommon();
        if (!isset($arrCmd) || empty($arrCmd) || count($arrCmd) !== 5) {
            Log::info('the wrong cmd is: '.json_encode($arrCmd));
            return false;
        }

        array_push($arrCmd, $this->params['releaseId']);
        $rollbackObj = new DeployServices($this->action);
        $res = $rollbackObj->doShellCmd($arrCmd);
        if (false === $res) {
            Log::info('the action: '.$this->action.' deploy failed.check it.');
            return false;
        } else if (is_array($res)) {
            Log::info('the acton: '.$this->action.' & the res is: '.json_encode($res));
            if (!isset($res['releaseId']) || !isset($res['logPath'])) {
                $status = 2;
            } else {
                $status = 1;
            }

            $this->modifyStatus($status, $res);

            // 回滚执行清除cache操作
            $this->clearCache();
        }
    }

    /**
     * 要处理的失败任务
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // 给用户发送失败通知，等等。。。
        Log::info('the queue is failed ,check it ,'.$exception->getMessage());
        $status = 1;
        $this->modifyStatus($status);
        return false;
    }
}
