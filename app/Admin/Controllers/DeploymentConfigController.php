<?php

namespace App\Admin\Controllers;

use App\DeploymentConfig;
use App\Http\Controllers\Controller;
use App\Services\UtilsService;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use function foo\func;
use Illuminate\Support\MessageBag;

class DeploymentConfigController extends Controller
{
    use HasResourceActions;

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
            ->header('Index')
            ->description('description')
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
            ->header('Detail')
            ->description('description')
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
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
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
            ->description('新增部署主机的各个配置项')
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
        $grid->model()->orderBy('id', 'desc');
        $grid->id('配置id')->sortabled();
        $grid->config_name('配置名');
        $grid->config_env('配置环境')->display(function ($config_env) {
            $arr = config('deployment.deploy_config.task_env');
            if (isset($arr[$config_env])) {
                return $arr[$config_env].'环境';
            }
        });
        $grid->config_user('权限用户');
        $grid->config_from('部署源');
        $grid->config_host_path('目标路径');
        $grid->config_releases('版本数量');
        $grid->config_exlude('排除部署目录');
        $grid->config_hosts('部署主机');
        $grid->config_pre_deploy('部署前命令')->display(function ($config_pre_deploy) {
            $arr = config('deployment.deploy_config.pre-deploy');
            $preArr = explode(',', $config_pre_deploy);
            $line = '';
            $custom = 'custom_';
            $len = strlen($custom);
            foreach ($preArr as $key => $value) {
                if (strpos($value, $custom) === false && isset($arr[$value])) {
                    $line .= $arr[$value] . '&';
                } else if (($pos = strpos($value, $custom)) !== false) {
                    $line .= substr($value, $len).'&';
                }
            }

            return rtrim($line, '&');
        });

        $grid->config_on_deploy('部署时命令')->display(function ($config_on_deploy) {
            $arr = config('deployment.deploy_config.on-deploy');
            $preArr = explode(',', $config_on_deploy);
            $line = '';
            $custom = 'custom_';
            $len = strlen($custom);
            foreach ($preArr as $key => $value) {
                if (strpos($value, $custom) === false && isset($arr[$value])) {
                    $line .= $arr[$value] . '&';
                } else if (strpos($value, $custom) !== false) {
                    $line .= substr($value, $len).'&';
                }
            }

            return rtrim($line, '&');
        });
        $grid->config_on_release('发布时命令')->display(function ($config_on_release) {
            $arr = config('deployment.deploy_config.on-release');
            $preArr = explode(',', $config_on_release);
            $line = '';
            $custom = 'custom_';
            $len = strlen($custom);
            foreach ($preArr as $key => $value) {
                if (strpos($value, $custom) === false && isset($arr[$value])) {
                    $line .= $arr[$value] . '&';
                } else if (strpos($value, $custom) !== false) {
                    $line .= substr($value, $len).'&';
                }
            }

            return rtrim($line, '&');
        });
        $grid->config_post_release('发布后命令')->display(function ($config_post_release){
            $arr = config('deployment.deploy_config.post-release');
            $preArr = explode(',', $config_post_release);
            $line = '';
            $custom = 'custom_';
            $len = strlen($custom);
            foreach ($preArr as $key => $value) {
                if (strpos($value, $custom) === false && isset($arr[$value])) {
                    $line .= $arr[$value] . '&';
                } else if (strpos($value, $custom) !== false) {
                    $line .= substr($value, $len).'&';
                }
            }

            return rtrim($line, '&');
        });
        $grid->config_post_deploy('部署后命令')->display(function ($config_post_deploy){
            $arr = config('deployment.deploy_config.post-deploy');
            $preArr = explode(',', $config_post_deploy);
            $line = '';
            $custom = 'custom_';
            $len = strlen($custom);
            foreach ($preArr as $key => $value) {
                if (strpos($value, $custom) === false && isset($arr[$value])) {
                    $line .= $arr[$value] . '&';
                } else if (strpos($value, $custom) !== false) {
                    $line .= substr($value, $len).'&';
                }
            }

            return rtrim($line, '&');
        });
        $grid->updated_at('操作时间');

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
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



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $branchArr = config('deployment.deploy_config');
        $form = new Form(new DeploymentConfig);
        $form->text('config_name', '配置名称[name]')->placeholder('输入配置环境名称')->rules('required|min:3');
        $form->select('config_env', '部署环境[env]')->options($branchArr['task_env'])->placeholder('请选择部署环境');
        $form->text('config_user', '执行用户[user]')->placeholder('输入目标主机权限用户名')->rules('required|min:1');
        $form->text('config_from', '部署源路径[from]')->placeholder('输入部署文件所在路径')->rules('required|min:2');
        $form->text('config_host_path', '部署目标主机路径[host]')->placeholder('输入部署主机文件所在路径')->rules('required|min:3');
        $form->text('config_releases', '部署策略[release]')->placeholder('输入部署主机保留的版本数')->rules('required|min:1');
        $form->text('config_exlude', '排除源路径下文件/目录[exclude]')->placeholder('输入部署时排除的文件/目录')->rules('required|min:3');
        $form->text('config_hosts', '部署主机名/ip[hosts]')->placeholder('输入部署的主机列表(多个主机用,分割)')->rules('required|min:3');
        $form->checkbox('config_pre_deploy', '部署前执行的任务[可选:pre-deploy]')->options($branchArr['pre-deploy'])->placeholder('输入部署前该阶段执行的任务(多个任务用,分割)');
        $form->text('customize_pre_deploy', '部署前执行的任务[自定义:pre-deploy]')->placeholder('输入自定义执行的任务(多个任务用,分割)');
        $form->checkbox('config_on_deploy', '部署时执行的任务[on-deploy]')->options($branchArr['on-deploy'])->placeholder('输入部署时该阶段执行的任务(多个任务用,分割)');
        $form->text('customize_on_deploy', '部署时执行的任务[自定义:on-deploy]')->placeholder('输入自定义执行的任务(多个任务用,分割)');
        $form->checkbox('config_on_release', '发布时执行的任务[on-release]')->options($branchArr['on-release'])->placeholder('输入部署主机发布时执行的任务(多个任务用,分割)）');
        $form->text('customize_on_release', '发布时执行的任务[自定义:on-release]')->placeholder('输入自定义执行的任务(多个任务用,分割)');
        $form->checkbox('config_post_release', '发布后执行的任务[post-release]')->options($branchArr['post-release'])->placeholder('输入部署主机发布后执行的任务(多个任务用,分割)');
        $form->text('customize_post_release', '发布后执行的任务[自定义:post-release]')->placeholder('输入自定义执行的任务(多个任务用,分割)');
        $form->checkbox('config_post_deploy', '部署后执行的任务[post-deploy]')->options($branchArr['post-deploy'])->placeholder('输入部署后执行的任务(多个任务用,分割)');
        $form->text('customize_post_deploy', '部署后执行的任务[自定义:post-deploy]')->placeholder('输入自定义执行的任务(多个任务用,分割)');


        // 表单写入前判断
        $form->saving(function (Form $form){
            $allFields = $form->input(null);
            $postData = request()->all();
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

            // 校验pre-deploy入参 - begin
            $tmpDeployStr = '';
            $tmpDeployArr = [];
            if (is_array($form->input('config_pre_deploy'))) {
                foreach ($form->input('config_pre_deploy') as $key => $value) {
                    if ($value !== null) {
                        $tmpDeployStr .= $value .'#';
                        $tmpDeployArr[] = $value;
                    }
                }
            }
            if ($form->input('customize_pre_deploy') !== null) {
                $tmpDeployStr .= $form->input('customize_pre_deploy') .'#';
                $tmpDeployArr[] = 'custom_'.$form->input('customize_pre_deploy');
            }
            if ($postData['customize_pre_deploy'] !== null) {
                $tmpDeployArr[] = 'custom_'.$postData['customize_pre_deploy'];
            }

            $form->input('config_pre_deploy', $tmpDeployArr);
            // 校验pre-deploy入参 - end

            // 校验on-deploy入参 - begin
            $tmpDeployStr = '';
            $tmpDeployArr = [];
            if (is_array($form->input('config_on_deploy'))) {
                foreach ($form->input('config_on_deploy') as $key => $value) {
                    if ($value !== null) {
                        $tmpDeployStr .= $value .'#';
                        $tmpDeployArr[] = $value;
                    }
                }
            }
            if ($form->input('customize_on_deploy') !== null) {
                $tmpDeployStr .= $form->input('customize_on_deploy') .'#';
                $tmpDeployArr[] = 'custom_'.$form->input('customize_on_deploy');
            }
            if ($postData['customize_on_deploy'] !== null) {
                $tmpDeployArr[] = 'custom_'.$postData['customize_on_deploy'];
            }
            $form->input('config_on_deploy', $tmpDeployArr);
            // 校验on-deploy入参 - end

            // 校验on-release入参 - begin
            $tmpDeployStr = '';
            $tmpDeployArr = [];
            if (is_array($form->input('config_on_release'))) {
                foreach ($form->input('config_on_release') as $key => $value) {
                    if ($value !== null) {
                        $tmpDeployStr .= $value .'#';
                        $tmpDeployArr[] = $value;
                    }
                }
            }
            if ($form->input('customize_on_release') !== null) {
                $tmpDeployStr .= $form->input('customize_on_release') .'#';
                $tmpDeployArr[] = 'custom_'.$form->input('customize_on_release');
            }
            if ($postData['customize_on_release'] !== null) {
                $tmpDeployArr[] = 'custom_'.$postData['customize_on_release'];
            }
            $form->input('config_on_release', $tmpDeployArr);
            // 校验on-release入参 - end

            // 校验post-deploy入参 - begin
            $tmpDeployStr = '';
            $tmpDeployArr = [];
            if (is_array($form->input('config_post_release'))) {
                foreach ($form->input('config_post_release') as $key => $value) {
                    if ($value !== null) {
                        $tmpDeployStr .= $value .'#';
                        $tmpDeployArr[] = $value;
                    }
                }
            }
            if ($form->input('customize_post_release') !== null) {
                $tmpDeployStr .= $form->input('customize_post_release') .'#';
                $tmpDeployArr[] = 'custom_'.$form->input('customize_post_release');
            }
            if ($postData['customize_post_release'] !== null) {
                $tmpDeployArr[] = 'custom_'.$postData['customize_post_release'];
            }
            $form->input('config_post_release', $tmpDeployArr);
            // 校验post-release入参 - end

            // 校验post-release入参 - begin
            $tmpDeployStr = '';
            $tmpDeployArr = [];
            if (is_array($form->input('config_post_deploy'))) {
                foreach ($form->input('config_post_deploy') as $key => $value) {
                    if ($value !== null) {
                        $tmpDeployStr .= $value .'#';
                        $tmpDeployArr[] = $value;
                    }
                }
            }
            if ($form->input('customize_post_deploy') !== null) {
                $tmpDeployStr .= $form->input('customize_post_deploy') .'#';
                $tmpDeployArr[] = 'custom_'.$form->input('customize_post_deploy');
            }
            if ($postData['customize_post_deploy'] !== null) {
                $tmpDeployArr[] = 'custom_'.$postData['customize_post_deploy'];
            }
            //$form->input('config_post_deploy', rtrim($tmpDeployStr, '#'));
            $form->input('config_post_deploy', $tmpDeployArr);
            // 校验post-deploy入参 - end

        });

        // 限制保存字段
        $form->ignore(['customize_pre_deploy', 'customize_on_deploy', 'customize_on_release', 'customize_post_release', 'customize_post_deploy']);


        // 成功写表后的操作, 生产magephp部署的配置文件
        $form->saved(function (Form $form) {
            $retry_time = 3;
            while ($retry_time) {
                //var_dump(request()->all(), $form->input(null));
                $returnRes = UtilsService::generateConfigForMage($form->input(null));
                if ($returnRes) {
                    break;
                }
                $retry_time--;
            }
        });

        return $form;
    }
}
