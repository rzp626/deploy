<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\CheckRow;
use App\Admin\Extensions\Tools\UserGender;
use App\DeploymentConfig;
use App\Http\Controllers\Controller;
use App\Jobs\HandGitRepoJob;
use App\Services\GitRepoInfoService;
use App\Services\UtilsService;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\Request;

class DeploymentConfigController extends Controller
{
    use HasResourceActions;

    private $user;

    /**
     * DeploymentConfigController constructor.
     */
    public function __construct()
    {
        $this->user = new Admin();
    }

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $arr = config('deployment.deploy_config');
        return $content
            ->header('配置列表')
            ->description('')
            ->body($this->grid($arr));
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('查看')
            ->description('配置详情页')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑配置')
            ->description('重新编辑各个配置项')
            ->body($this->form($id)->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('新增部署配置')
            ->description('新增部署配置项目')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid($arr)
    {
        $grid = new Grid(new DeploymentConfig);
        $user = new Admin();
        $grid->model()->orderBy('id', 'desc');
        $grid->id('ID')->sortabled();
        $grid->config_name('项目名');
        $opt = '';
        $grid->config_env('配置环境')->display(function ($config_env) {
            $arr = config('deployment.deploy_config.task_env');
            if (isset($arr[$config_env])) {
                return $arr[$config_env];
            }

            return '';
        });
        $grid->config_branch('选取分支')->display(function ($config_branch) {
            $arr = config('deployment.deploy_config.task_branch');
            if (isset($arr[$config_branch])) {
                return $arr[$config_branch];
            }

            return '';
        });

        $grid->config_ssh_addr('项目ssh地址');
        $grid->custom_config_branch('自定义部署分支');
        $grid->updated_at('创建时间');
        $grid->operator('操作人');
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });

//            $tools->append(new UserGender());
        });

//        if (in_array(Request::get('gender'), ['m', 'f'])) {
//            $grid->model()->where('gender', Request::get('gender'));
//        }

        $grid->actions(function (Grid\Displayers\Actions $actions) use($user) {
            if (($actions->row->operator) != ($user->user()->username)) {
                $actions->disableEdit();
                $actions->disableDelete();
            }

//            $actions->append(new CheckRow($actions->getKey()));
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(DeploymentConfig::findOrFail($id));
        $configArr = config('deployment.deploy_config');
        $envArr = $configArr['task_env'];
        $branchArr = $configArr['task_branch'];
        $preDeployArr = $configArr['pre-deploy'];
        $onDeployArr = $configArr['on-deploy'];
        $onReleaseArr = $configArr['on-release'];
        $postReleaseArr = $configArr['post-release'];
        $postDeployArr = $configArr['post-deploy'];

        $show->config_name('项目名');
        $show->config_env('部署环境')->as(function ($config_env) use ($envArr) {
            return $envArr[$config_env].'环境';
        });
        $show->config_user('权限用户');
        $show->config_branch('选取分支')->as(function ($config_branch) use ($branchArr) {
            return $branchArr[$config_branch].'分支';
        });
        $show->config_from('源路径');
        $show->config_releases('备份数量');
        $show->config_host_path('目标主机路径');
        $show->config_exlude('非部署目录')->as(function ($config_exlude) {
            $arr = explode('|', $config_exlude);
            if (count($arr) > 1) {
                $str = implode("<br>", $arr);
                return $str;
            } else {
                return $config_exlude;
            }
        });
        $show->config_hosts('部署主机')->as(function ($config_hosts) {
            $arr = explode('|', $config_hosts);
            if (count($arr) > 1) {
                $str = implode("<br>", $arr);
                return $str;
            } else {
                return $config_hosts;
            }
        });
        $show->divider();
        $show->config_pre_deploy('pre-deploy任务')->as(function ($config_pre_deploy) use($preDeployArr) {
            if (empty($config_pre_deploy)) {
                return '';
            }

            $arr = explode('|', $config_pre_deploy);
            $str = '';
            foreach ($arr as $k => $v) {
                $str .= $preDeployArr[$v] . '|';
            }

            return rtrim($str, '|');
        });
        $show->custom_pre_deploy('pre-deploy自定义任务');
        $show->config_on_deploy('on-deploy命令')->as(function ($config_on_deploy) use($onDeployArr) {
            if (empty($config_on_deploy)) {
                return '';
            }

            $arr = explode('|', $config_on_deploy);
            $str = '';
            foreach ($arr as $k => $v) {
                $str .= $onDeployArr[$v] . '|';
            }

            return rtrim($str, '|');
        });
        $show->custom_on_deploy('on-deploy自定义命令');

        $show->config_on_release('on-release命令')->as(function ($config_on_release) use($onReleaseArr) {
            if (empty($config_on_release)) {
                return '';
            }

            $arr = explode('|', $config_on_release);
            $str = '';
            foreach ($arr as $k => $v) {
                $str .= $onReleaseArr[$v] . '|';
            }

            return rtrim($str, '|');
        });
        $show->custom_on_release('on-release自定义命令');

        $show->config_post_release('post-release命令')->as(function ($config_post_release) use($postReleaseArr) {
            if (empty($config_post_release)) {
                return '';
            }

            $arr = explode('|', $config_post_release);
            $str = '';
            foreach ($arr as $k => $v) {
                $str .= $postReleaseArr[$v] . '|';
            }

            return rtrim($str, '|');
        });
        $show->custom_post_release('post-release自定义命令');

        $show->config_post_deploy('post-deploy命令')->as(function ($config_post_deploy) use($postDeployArr) {
            if (empty($config_post_deploy)) {
                return '';
            }

            $arr = explode('|', $config_post_deploy);
            $str = '';
            foreach ($arr as $k => $v) {
                $str .= $postDeployArr[$v] . '|';
            }

            return rtrim($str, '|');
        });
        $show->custom_post_deploy('post-deploy自定义命令');

        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
//                $tools->disableList();
                $tools->disableDelete();
            });
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DeploymentConfig);
        $form->tab('配置基本项', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $phpVersionArr = config('deployment.php_version');
            $form->text('config_name', '项目名')->placeholder('输入配置环境名称')->rules('required|min:3');
            $form->text('config_ssh_addr', '项目ssh地址')->placeholder('输入项目仓库地址')->rules('required|min:3');
            $form->select('config_php_version', '项目php版本')->options($phpVersionArr)->placeholder('选择php版本');
            $form->select('config_env', '部署环境')->options($branchArr['task_env'])->placeholder('请选择部署环境')->rules('required|min:1');
            $form->text('config_user', '权限用户')->placeholder('输入目标主机权限用户名')->rules('required|min:1');
            $form->select('config_branch', '选取分支')->options($branchArr['task_branch'])->placeholder('选择部署分支')->rules('required|min:1');
            $form->text('custom_config_branch', '自定义分支')->placeholder('可输入自定义部署分支')->default('');
            $form->hidden('config_from', '源路径')->placeholder('输入部署文件所在路径')->default('');;
            $form->text('config_host_path', '目标主机路径')->placeholder('输入部署主机文件所在路径')->rules('required|min:3');
            $form->text('config_releases', '备份数量')->placeholder('输入部署主机保留的版本数')->rules('required|min:1');
            $form->textarea('config_exlude', '非部署目录/文件')->placeholder('输入部署时排除的文件/目录')->rows(5);
            $form->textarea('config_hosts', '部署主机')->placeholder('一行一主机')->rows(10)->rules('required|min:3');
        })->tab('pre-deploy阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_pre_deploy', '[可选]')->options($branchArr['pre-deploy'])->placeholder('输入部署前该阶段执行的任务(多个任务用|分割)')->stacked();
            $form->textarea('custom_pre_deploy', '[自定义]')->rows(5)->placeholder('输入自定义执行的任务(一行一命令)');
        })->tab('on-deploy阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_on_deploy', '[可选]')->options($branchArr['on-deploy'])->placeholder('输入部署时该阶段执行的任务(多个任务用|分割)')->stacked();
            $form->textarea('custom_on_deploy', '[自定义]')->rows(5)->placeholder('输入自定义执行的任务(一行一命令)');
        })->tab('on-release阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_on_release', '[可选]')->options($branchArr['on-release'])->placeholder('输入部署主机发布时执行的任务(多个任务用|分割)）')->stacked();
            $form->textarea('custom_on_release', '[自定义]')->rows(5)->placeholder('输入自定义执行的任务(一行一命令)');

        })->tab('post-release阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_post_release', '[可选]')->options($branchArr['post-release'])->placeholder('输入部署主机发布后执行的任务(多个任务用|分割)')->stacked();
            $form->textarea('custom_post_release', '[自定义]')->rows(5)->placeholder('输入自定义执行的任务(一行一命令)');

        })->tab('post-deploy阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_post_deploy', '[可选]')->options($branchArr['post-deploy'])->placeholder('输入部署后执行的任务(多个任务用|分割)')->stacked();
            $form->textarea('custom_post_deploy', '[自定义]')->rows(5)->placeholder('输入自定义执行的任务(一行一命令)');
        });

        $form->hidden('operator')->default('');
        $username = $this->user->user()->username;
        if ($username) {
            Log::info('the operator is wrong: '. $username .'The time is '.time());
            $form->input('operator', $username);
        }

        $form->tools(function (Form\Tools $tools) {
            // 去掉 '列表' 按钮
            $tools->disableList();

            // 取缔 '删除' 按钮
            $tools->disableDelete();

            // 去掉 '查看' 按钮
            $tools->disableView();

            // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
//            $tools->add('<a class="btn btn-sm btn-danger"><i class="fa fa-trash"></i>&nbsp;&nbsp;delete</a>');
        });
//        $form->ignore(['custom_config_branch']);
        // 表单写入前判断
        $form->saving(function (Form $form){
            $allFields = $form->input(null);
            $filledFields = config('deployment.filled_fields');
            $initFields = config('deployment.init_fields');
            $filterRes = UtilsService::filterFields($allFields, $filledFields, $initFields);
            if (false === $filterRes) {
                $error = new MessageBag([
                    'title' => '参数有误',
                    'message' => '请检查必填配置项',
                ]);
                return back()->with(compact('error'));
            }
            $customBranch = $form->input('custom_config_branch');
            if (empty($customBranch)) {
                $form->input('custom_config_branch', '');
            }

            $configFrom = $form->input('config_from');
            if (empty($configFrom)) {
                $form->input('config_from', '');
            }

            // 排除目录/文件
            if ($form->input('config_exlude') && !empty($form->input('config_exlude'))) {
                if (null !== strpos($form->input('config_exlude'), '|')) { // 未解决的原因
                    $str = $form->input('config_exlude');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('config_exlude', $str);
                }
                $arr = explode("\r\n", $form->input('config_exlude'));
                $arr = array_filter($arr);
                $form->input('config_exlude', $arr);
            }

            // 处理主机
            if ($form->input('config_hosts') && !empty($form->input('config_hosts'))) {
                if (null !== strpos($form->input('config_hosts'), '|')) { // 未解决的原因
                    $str = $form->input('config_hosts');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('config_hosts', $str);
                }
                $arr = explode("\r\n", $form->input('config_hosts'));
                $arr = array_filter($arr);
                $form->input('config_hosts', $arr);
            }

            // 校验pre-deploy入参 - begin
            $tmpDeployArr = [];
            if (is_array($form->input('config_pre_deploy'))) {
                $cnt = count($form->input('config_pre_deploy'));
                if ($cnt == 1) {
                    if (null === $form->input('config_pre_deploy')[$cnt - 1]) {
                        $tmpDeployArr = [];
                    }
                } else {
                    foreach ($form->input('config_pre_deploy') as $key => $value) {
                        if ($value !== null) {
                            $tmpDeployArr[] = $value;
                        }
                    }
                }
            }

            $form->input('config_pre_deploy', $tmpDeployArr);
            if (null === $form->input('custom_pre_deploy')) {
                $form->input('custom_pre_deploy', []);
            } else {
                if (null !== strpos($form->input('custom_pre_deploy'), '|')) { // 未解决的原因
                    $str = $form->input('custom_pre_deploy');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('custom_pre_deploy', $str);
                }
                $arr = explode("\r\n", $form->input('custom_pre_deploy'));
                $arr = array_filter($arr);
                $form->input('custom_pre_deploy', $arr);
            }
            // 校验pre-deploy入参 - end

            // 校验on-deploy入参 - begin
            $tmpDeployArr = [];
            if (is_array($form->input('config_on_deploy'))) {
                $cnt = count($form->input('config_on_deploy'));
                if ($cnt == 1) {
                    if (null === $form->input('config_on_deploy')[$cnt - 1]) {
                        $tmpDeployArr = [];
                    }
                } else {
                    foreach ($form->input('config_on_deploy') as $key => $value) {
                        if ($value !== null) {
                            $tmpDeployArr[] = $value;
                        }
                    }
                }
            }
            $form->input('config_on_deploy', $tmpDeployArr);
            if (null === $form->input('custom_on_deploy')) {
                $form->input('custom_on_deploy', []);
            } else {
                if (null !== strpos($form->input('custom_on_deploy'), '|')) { // 未解决的原因
                    $str = $form->input('custom_on_deploy');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('custom_on_deploy', $str);
                }
                $arr = explode("\r\n", $form->input('custom_on_deploy'));
                $arr = array_filter($arr);
                $form->input('custom_on_deploy', $arr);
            }
            // 校验on-deploy入参 - end

            // 校验on-release入参 - begin
            $tmpDeployArr = [];
            if (is_array($form->input('config_on_release'))) {
                $cnt = count($form->input('config_on_release'));
                if ($cnt == 1) {
                    if (null === $form->input('config_on_release')[$cnt - 1]) {
                        $tmpDeployArr = [];
                    }
                } else {
                    foreach ($form->input('config_on_release') as $key => $value) {
                        if ($value !== null) {
                            $tmpDeployArr[] = $value;
                        }
                    }
                }
            }
            $form->input('config_on_release', $tmpDeployArr);
            if (null === $form->input('custom_on_release')) {
                $form->input('custom_on_release', []);
            } else {
                if (null !== strpos($form->input('custom_on_release'), '|')) { // 未解决的原因
                    $str = $form->input('custom_on_release');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('custom_on_release', $str);
                }
                $arr = explode("\r\n", $form->input('custom_on_release'));
                $arr = array_filter($arr);
                $form->input('custom_on_release', $arr);
            }
            // 校验on-release入参 - end

            // 校验post-deploy入参 - begin
            $tmpDeployArr = [];
            if (is_array($form->input('config_post_release'))) {
                $cnt = count($form->input('config_post_release'));
                if ($cnt == 1) {
                    if (null === $form->input('config_post_release')[$cnt - 1]) {
                        $tmpDeployArr = [];
                    }
                } else {
                    foreach ($form->input('config_post_release') as $key => $value) {
                        if ($value !== null) {
                            $tmpDeployArr[] = $value;
                        }
                    }
                }
            }
            $form->input('config_post_release', $tmpDeployArr);
            if (null === $form->input('custom_post_release')) {
                $form->input('custom_post_release', []);
            } else {
                if (null !== strpos($form->input('custom_post_release'), '|')) { // 未解决的原因
                    $str = $form->input('custom_post_release');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('custom_post_release', $str);
                }
                $arr = explode("\r\n", $form->input('custom_post_release'));
                $arr = array_filter($arr);
                $form->input('custom_post_release', $arr);
            }
            // 校验post-release入参 - end

            // 校验post-release入参 - begin
            $tmpDeployArr = [];
            if (is_array($form->input('config_post_deploy'))) {
                $cnt = count($form->input('config_post_deploy'));
                if ($cnt == 1) {
                    if (null === $form->input('config_post_deploy')[$cnt - 1]) {
                        $tmpDeployArr = [];
                    }
                } else {
                    foreach ($form->input('config_post_deploy') as $key => $value) {
                        if ($value !== null) {
                            $tmpDeployArr[] = $value;
                        }
                    }
                }
            }
            $form->input('config_post_deploy', $tmpDeployArr);
            if (null === $form->input('custom_post_deploy')) {
                $form->input('custom_post_deploy', []);
            } else {
                if (null !== strpos($form->input('custom_post_deploy'), '|')) { // 未解决的原因
                    $str = $form->input('custom_post_deploy');
                    $str = str_replace('|', "\r\n", $str);
                    $form->input('custom_post_deploy', $str);
                }
                $arr = explode("\r\n", $form->input('custom_post_deploy'));
                $arr = array_filter($arr);
                $form->input('custom_post_deploy', $arr);
            }
            // 校验post-deploy入参 - end

        });

        // 成功写表后的操作, 生产magephp部署的配置文件
        $form->saved(function (Form $form) {
            // git clone | pull repo操作
            $configUser = $form->input('config_user');
            $branch = $form->input('config_branch');
            $customBranch = $form->input('custom_config_branch');
            $sshAddr = $form->input('config_ssh_addr');
            if ($customBranch !== 0 && !empty($customBranch) && strlen($customBranch) > 0) {
                $branch = $customBranch;
            }
            $configId = $form->model()->id;
//            GitRepoInfoService::handleQueue($form->input(null), $configId);
//            GitRepoInfoService::getGitInfo($sshAddr, $configUser, $branch, $configId);
//            $this->dispatch(new HandGitRepoJob($form->input(null), $configId));
            $job = (new HandGitRepoJob($form->input(null), $configId))->onQueue('handle_git_repo');
            dispatch($job);

            $retry_time = 3;
            while ($retry_time) {
                //var_dump(request()->all(), $form->input(null));
                $returnRes = UtilsService::generateConfigForMage($form->input(null), $configId);
                if ($returnRes) {
                    break;
                }
                $retry_time--;
            }
        });

        return $form;
    }
}
