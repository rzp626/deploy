<?php
namespace App\Admin\Controllers;

use App\DeploymentConfig;
use App\DeploymentTask;
use App\Http\Controllers\Controller;
use App\Jobs\ReviewEmailJob;
use App\Jobs\SendMailJob;
use App\Services\UtilsService;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Log;
use App\Jobs\DeployOptJob;
use Encore\Admin\Admin;
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

        try {
            // 修改发布状态为进行中。。
            $taskModel = DeploymentTask::find($params['taskId']);
            $taskModel->task_status = 1;
            $taskModel->save();
        } catch (\Exception $e) {
            Log::info('modify the task status failed, the task id: '.json_encode($params));
            $data = [
                'code' => '400',
                'msg' => '发布失败，请重试.',
            ];
            return response()->json($data);
        }

        $this->dispatch(new DeployOptJob('deploy', $params));
        $data = [
            'code' => '200',
            'msg' => '发布队列添加成功，请稍后刷新页面查看结果',
        ];
        return response()->json($data);
    }

    /**
     * 执行回滚操作
     * @param $releaseId
     * @return view
     */
    public function rollback(Request $request, Admin $admin)
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

        try {
            // 修改发布状态为进行中。。
            $taskModel = DeploymentTask::find($ids['taskId']);
            $taskModel->release_status = 3;
            $taskModel->save();
        } catch (\Exception $e) {
            Log::info('modify the task status failed, the task id: '.json_encode($ids));
            $data = [
                'code' => '400',
                'msg' => '回滚失败，请重试.',
            ];
            return response()->json($data);
        }

        $this->dispatch(new DeployOptJob('releases:rollback', $ids));
        $this->dispatch(new SendMailJob(new ReviewEmailJob($admin->user()->username, 'rollback')));
        $data = [
            'code' => '200',
            'msg' => '回滚队列添加成功，等待执行结果',
        ];
        return response()->json($data);
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
