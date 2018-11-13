<?php
namespace App\Admin\Controllers;

use App\DeploymentConfig;
use App\DeploymentTask;
use App\Http\Controllers\Controller;
use App\Services\UtilsService;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Log;
use App\Jobs\DeployOptJob;
set_time_limit(0);
//sleep(15);

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
    public function deploy(Request $request)
    {
        $id = $request->get('id');
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

        $res = $this->dispatch(new DeployOptJob('deploy', $params));
        Log::info('the res: '.print_r($res, true));
        $data = [
            'code' => '200',
            'msg' => '发布队列添加成功',
        ];
        return response()->json($data);

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
        $cmd = ['nohup', $phpPath, $vendorMageBin, 'deploy', $env_name];
        $res = $this->doShellCmd($cmd);

        $task_status = 3; // 执行失败
        if ($res) { // 执行成功
            $task_status = 2;
            $data = [
                'code' => '200',
                'msg' => '发布成功',
                'page' => $this->sendOutputTo,
            ];
        } else {
            $data = [
                'code' => '400',
                'msg' => '发布失败',
                'page' => $this->sendOutputTo,
            ];
        }

        // 后面加上sql语句执行的log
        $deployModel = DeploymentTask::find($taskId);
        $deployModel->task_status = $task_status;
        $deployModel->release_id = $this->releaseId;
        $deployModel->save();

        return response()->json($data);

        // 执行完成后，页面显示
        $data = $this->readOutput();
        $data = UtilsService::getFileContent($this->sendOutputTo);
        return view('deploy.index')->with('data', $data);
    }

    /**
     * 执行回滚操作
     * @param $releaseId
     * @return view
     */
    public function rollback(Request $request)
    {
        $releaseId = $request->get('id');
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

        $res = $this->dispatch(new DeployOptJob('releases:rollback', $ids));
        Log::info('the res: '.json_encode($res));
        $data = [
            'code' => '200',
            'msg' => '发布队列添加成功',
        ];
        return response()->json($data);
        return $releaseId;


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
        $config_hosts = $info->config_hosts; // 远程主机地址
        $config_hosts_path = rtrim($info->config_host_path, '/'); // 远程主机目录
        $config_path = $info->config_from;
        chdir($config_path);
        Log::info('now the path== '.getcwd());
        $phpPath = $this->getBinPath();
        $cmd = ['nohup', $phpPath, $vendorMageBin, 'releases:rollback', $env_name, $ids['releaseId']];
        $res = $this->doShellCmd($cmd);

        $release_status = 2; // 执行失败
        if ($res) { // 执行成功
            $release_status = 1;
            $data = [
                'code' => '200',
                'msg' => '回滚成功',
                'page' => $this->sendOutputTo,
            ];
            // 分别清除各个主机opcache
            $dirArr = explode('|', $config_hosts);
            foreach ($dirArr as $dir) {
                if (false !== strpos($dir, ':')) {
                    $realDir = explode(":", $dir);
                    $path = $realDir[0];
                } else {
                    $path = $dir;
                }

                $cmd = "ssh root@$path -p 26 'cd $config_hosts_path/current;/usr/local/sina_mobile/php7/bin/php cachetool opcache:reset --fcgi=127.0.0.1:9000'";
                Log::info('the rollback opcache cmd: '.$cmd);
                exec($cmd, $output, $result);
                if ((int)$result !== 0) {
                    Log::info('the '.$dir.' clear opcache failed. the outputis'.json_encode($output).', the return value is '.$result);
                } else {
                    Log::info('Rollback to clear opcache successfully, the return value is '.$result);
                }
            }
        } else {
            $data = [
                'code' => '400',
                'msg' => '回滚失败',
                'page' => $this->sendOutputTo,
            ];
        }

        $deployModel = DeploymentTask::find($taskId);
        $deployModel->release_status = $release_status;
        $deployModel->released_at = date("Y-m-d H:i:s", time());
        $deployModel->save();

        return response()->json($data);

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
            Log::info('error: '.json_encode($process->getErrorOutput()));
            Log::info(json_encode($processCmd).'执行失败。');
            return false;
        }

        return true;
    }

    /**
     * 获取bin路径
     *
     * @return string
     */
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

    public function showLog(Request $request)
    {
        $releaseId = $request->get('id');
        if (!isset($releaseId) || empty($releaseId)) {
            header('Cache-control: private, must-revalidate');
            echo "<script>alert('查看执行日志操作失败,请检查.');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        // 根据发布id，获取log文件
        $logDir = '/data0/deploy/opt';
        if (!is_dir($logDir)) {
            header('Cache-control: private, must-revalidate');
            echo "<script>alert('执行日志不存在,请检查.');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }

        $fileArr = UtilsService::searchFile($logDir, $releaseId);
        $searchSum = count($fileArr);
        Log::info("搜索关键字: -- $releaseId -- 搜索目录: $logDir, 搜索结果: $searchSum");
        if ($searchSum <= 0) {
            echo "没有搜索到任何结果";
        } else {
            rsort($fileArr);
            $str = '';
            foreach ($fileArr as $key => $file) {
                $str .= UtilsService::getFileContent($file);
                $str .= PHP_EOL.'=========================================================='.PHP_EOL.'不同阶段分割线'.PHP_EOL.'=========================================================='.PHP_EOL.PHP_EOL;
            }
            return view('deploy.index')->with('data', $str);
        }
    }


    public function test(Request $request)
    {
        sleep(5);
        $id = $request->get('id');
        return $id;
    }
}
