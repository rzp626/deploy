<?php
namespace App\Admin\Controllers;

use App\DeploymentConfig;
use App\DeploymentTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Log;
use Mage\MageApplication;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DeployController extends Controller
{
    const RELEASE_ID_LEN = 14;
    const OPT_DEPLOY = 'deploy';
    const OPT_ROLLBACK = 'rollback';
    /**
     * @var string out put file for command.
     */
    protected $sendOutputTo;

    /**
     * @var string out out file name
     */
    protected $releaseId;

    /**
     * @var string operation for deploy or rollback
     */
    protected $currentOp;

    /**
     * @return void
     */
    protected function setReleasesNo()
    {
        $releaseFile = config_path().'/mage/releases';
        if (!file_exists($releaseFile)) {
            touch($releaseFile);
        }

        $lines = [];
        $fd = fopen($releaseFile, 'r');
        $locked = flock($fd, LOCK_NB | LOCK_EX);
        if($locked) {
           echo "GOT LOCK\n";
           while (!feof($fd)) {
                $line = fgets($fd);
                if (!empty($line)) {
                    $lines[] = $line;
                }
            }

            array_unshift($lines, $this->releaseId);
            if (count($lines) > 5) {
                $lines = array_slice($lines, 0, 4);
            }
            $string = implode("\n", $lines);

            fwrite($fd, $string);
            flock($fd, LOCK_UN);
        }
        fclose($fd);
    }

    /**
     * @return string
     */
    protected function getOutputTo()
    {
        if (!$this->releaseId) {
            $path = '/data0/deploy/error/op.output';
        } else {
            $path = '/data0/deploy/opt/'.$this->currentOp.'-releaseId-'.$this->releaseId.'.output';
        }

        if (!$this->sendOutputTo) {
            //$this->sendOutputTo = storage_path($path);
            $this->sendOutputTo = $path;
        }

        return $this->sendOutputTo;
    }

    /**
     * Read output info from output file.
     *
     * @return string
     */
    protected function readOutput()
    {
        return file_get_contents($this->getOutputTo());
    }

    /**
     * Send the output of the command to a given location.
     *
     * @param  string  $location
     * @param  string  $data
     * @param  bool  $append
     * @return int|false
     */
    protected function sendOutputTo($location, $data, $append = FILE_APPEND)
    {
        //return file_put_contents($location, $data, $append);
        return file_put_contents($location, $data);
    }

    /**
     * 执行发布、回滚操作
     * @param $id
     * @return view
     */
    public function deploy($id)
    {
        if (!isset($id) || empty($id)) {
            header('Cache-control: private, must-revalidate');
            echo "<script>alert('参数有误');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }

        $params = [];
        list($params['taskId'], $params['envId'], $params['branchId'], $params['configId']) = explode('-', $id);
        if (empty($params) || (count($params) !== 4)) {
            header('Cache-control: private, must-revalidate');
            echo "<script>alert('参数有误');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }

        $this->currentOp = 'deploy';
        $taskBranchArr = config('deployment.deploy_config.task_branch');
        $taskEnvArr = config('deployment.deploy_config.task_env');

        $env_name = $taskEnvArr[$params['envId']];
        $branch_name = $taskBranchArr[$params['branchId']];
        $info = DeploymentConfig::where('id', $params['configId'])->first();
        $config_path = $info->config_from;
        $config_env = $info->config_env;
        $config_branch = $info->config_branch;
        $taskId = $params['taskId'];
        $vendorMageBin = base_path().'/vendor/bin/mage';
        if (!file_exists($vendorMageBin)) {
            echo "magephp可执行文件，未发现，请检查.";
            exit;
        }

        chdir($config_path);
        Log::info('now the path== '.getcwd());
        $phpPath = $this->getBinPath();
        $cmd = [$phpPath, $vendorMageBin, 'deploy', $env_name];
        $res = $this->doShellCmd($cmd);

        $task_status = 3; // 执行失败
        if ($res) { // 执行成功
            $task_status = 2;
        }
        $deployModel = DeploymentTask::find($taskId);
        $deployModel->task_status = $task_status;
        $deployModel->release_id = $this->releaseId;
        $deployModel->save();

        // 执行完成后，页面显示
        $data = $this->readOutput();
        return view('deploy.index')->with('data', $data);
    }

    /**
     * 执行回滚操作
     * @param $releaseId
     * @return view
     */
    public function rollback($releaseId)
    {
        if (empty($releaseId)) {
            header('Cache-control: private, must-revalidate');
            echo "<script>alert('回滚操作失败,请检查.');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        // 切割参数
        $ids = [];
        list($ids['taskId'], $ids['envId'], $ids['branchId'], $ids['configId'], $ids['releaseId']) = explode('-', $releaseId);
        if (empty($ids) || empty($ids['taskId']) || empty($ids['releaseId']) || strlen($ids['releaseId']) !== self::RELEASE_ID_LEN) {
            header('Cache-control: private, must-revalidate');
            echo "<script>alert('回滚参数有误，请检查.');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }

        $this->currentOp = 'rollback';
        $taskBranchArr = config('deployment.deploy_config.task_branch');
        $taskEnvArr = config('deployment.deploy_config.task_env');

        $branchName = $taskBranchArr[$ids['branchId']];
        $env_name = $taskEnvArr[$ids['envId']];

        $taskId = $ids['taskId'];
        $vendorMageBin = base_path().'/vendor/bin/mage';
        if (!file_exists($vendorMageBin)) {
            echo "magephp可执行文件，未发现，请检查.";
            exit;
        }

        $info = DeploymentConfig::where('id', $ids['configId'])->first();
        $config_path = $info->config_from;
        chdir($config_path);
        Log::info('now the path== '.getcwd());
        $phpPath = $this->getBinPath();
        $cmd = [$phpPath, $vendorMageBin, 'releases:rollback', $env_name, $ids['releaseId']];
        $res = $this->doShellCmd($cmd);

        $release_status = 2; // 执行失败
        if ($res) { // 执行成功
            $release_status = 1;
        }
        $deployModel = DeploymentTask::find($taskId);
        $deployModel->release_status = $release_status;
        $deployModel->released_at = date("Y-m-d H:i:s", time());
        $deployModel->save();

        // 执行完成后，页面显示
        $data = $this->readOutput();
        return view('deploy.index')->with('data', $data);
    }

    /**
     * @param $processCmd
     * @return bool
     */
    protected function doShellCmd($processCmd)
    {
        $process = new Process($processCmd);
        $process->setTimeout(360);
        $process->start();
        $iterator = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT);
        $string = '';
        foreach ($iterator as $data) {
            if (!$this->releaseId) {
                $releaseArr = explode(" ", ltrim(trim($data, "\n")));
                if ($this->currentOp == self::OPT_DEPLOY) { // 部署
                    if (false !== strpos($data, "Release ID:")) {
                        $this->releaseId = !isset($releaseArr[2]) ? null : rtrim($releaseArr[2]);
                    }
                } else if ($this->currentOp == self::OPT_ROLLBACK) { // 回滚
                    if (false !== strpos($data, "Rollback to")) {
                        $this->releaseId = !isset($releaseArr[4]) ? null : rtrim($releaseArr[4]);
                    }
                }
            }
            $string .= $data;
        }
	
        $this->sendOutputTo($this->getOutputTo(), $string);

        if (!$process->isSuccessful()) { // deploy 失败，操作失败的提示信息
            $this->consoleLog('error: '.json_encode($process->getErrorOutput()));
            $this->consoleLog(json_encode($processCmd).'执行失败。');
            return false;
        }

        return true;
    }

    /**
     * @param $str
     * @param array $logInfo
     */
    private function consoleLog($str, $logInfo = [])
    {
        echo __CLASS__ . ":" . $str . PHP_EOL;
        Log::info(__CLASS__ . ":" . $str, $logInfo);
    }

    private function getBinPath()
    {
        if (PHP_BINARY) {
            $sbinPath = PHP_BINARY;
            $pos = strpos($sbinPath, '/sbin');
            $phpPath = substr($sbinPath, 0, $pos). '/bin/php';
        } else {
            $phpPath = '/usr/local/opt/php@7.1/bin/php'; // 测试bin路径
        }

        return $phpPath;
    }

    /**
     * 根据configId获取选择环境
     *
     * @param Request $request
     * @return mixed
     */
    public function selectEnv(Request $request)
    {
        $configId = $request->get('q');
        $data = DeploymentConfig::getConfigData( $configId);
        $taskEnvArr = config('deployment.deploy_config.task_env');
        $arr = [];
        $arr[0]['id'] = $data['id'].'-'.$data['config_env'];
        $arr[0]['text'] = $taskEnvArr[$data['config_env']];

        return $arr;
    }

    /**
     * 根据configId获取选择分支
     *
     * @param Request $request
     * @return mixed
     */
    public function selectBranch(Request $request)
    {
        $env = $request->get('q');
        $params = [];
        list($params['configId'], $params['envId']) = explode('-', $env);
        $taskBranchArr = config('deployment.deploy_config.task_branch');
        $data = DeploymentConfig::getConfigData($params['configId']);
        $arr = [];
        $arr[0]['id'] = $data['config_branch'];
        $arr[0]['text'] = $taskBranchArr[$data['config_branch']];
        return $arr;
    }

    public function selectConfigName()
    {
        $options = DeploymentConfig::select('id', 'config_name as text')->orderBy('id', 'desc')->get();
        $selection = [];
        $i = 0;
        foreach ($options as $k => $v) {
            $selection[$i]['id']    = $v->id;
            $selection[$i]['text']    = $v->text;
            $i++;
        }

        return $selection;
    }
}
