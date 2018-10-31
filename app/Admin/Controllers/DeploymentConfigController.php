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
        $grid->model()->orderBy('id', 'desc');
        $grid->id('ID')->sortabled();
        $grid->config_name('项目名');
        $grid->config_env('配置环境')->display(function ($config_env) {
            $arr = config('deployment.deploy_config.task_env');
            if (isset($arr[$config_env])) {
                return $arr[$config_env].'环境';
            }

            return '';
        });
        $grid->config_branch('选取分支')->display(function ($config_branch) {
            $arr = config('deployment.deploy_config.task_branch');
            if (isset($arr[$config_branch])) {
                return $arr[$config_branch].'分支';
            }

            return '';
        });

        $grid->updated_at('创建时间');

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
        $show->config_name('项目名');
        $show->config_env('部署环境');
        $show->config_user('权限用户');
        $show->config_branch('选取分支');
        $show->config_from('源路径');
        $show->config_host_path('目标主机路径');
        $show->config_exlude('非部署目录');
        $show->config_hosts('部署主机');
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
        $form->disableReset();
        $form->tab('配置基本项', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->text('config_name', '项目名')->placeholder('输入配置环境名称')->rules('required|min:3');
            $form->select('config_env', '部署环境')->options($branchArr['task_env'])->placeholder('请选择部署环境');
            $form->text('config_user', '权限用户')->placeholder('输入目标主机权限用户名')->rules('required|min:1');
            $form->select('config_branch', '选取分支')->options($branchArr['task_branch'])->placeholder('选择部署分支');
            $form->text('config_from', '源路径')->placeholder('输入部署文件所在路径')->rules('required|min:2');
            $form->text('config_host_path', '目标主机路径')->placeholder('输入部署主机文件所在路径')->rules('required|min:3');
            $form->text('config_releases', '部署策略')->placeholder('输入部署主机保留的版本数')->rules('required|min:1');
            $form->text('config_exlude', '非部署目录/文件')->placeholder('输入部署时排除的文件/目录')->rules('required|min:3');
            $form->text('config_hosts', '部署主机')->placeholder('输入部署的主机列表(多个主机用|分割)')->rules('required|min:3');
        })->tab('pre-deploy阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_pre_deploy', '[可选]')->options($branchArr['pre-deploy'])->placeholder('输入部署前该阶段执行的任务(多个任务用|分割)');
            $form->text('customize_pre_deploy', '[自定义]')->placeholder('输入自定义执行的任务(多个任务用|分割)');
        })->tab('on-deploy阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_on_deploy', '[可选]')->options($branchArr['on-deploy'])->placeholder('输入部署时该阶段执行的任务(多个任务用|分割)');
            $form->text('customize_on_deploy', '[自定义]')->placeholder('输入自定义执行的任务(多个任务用|分割)');
        })->tab('on-release阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_on_release', '[可选]')->options($branchArr['on-release'])->placeholder('输入部署主机发布时执行的任务(多个任务用|分割)）');
            $form->text('customize_on_release', '[自定义]')->placeholder('输入自定义执行的任务(多个任务用|分割)');

        })->tab('post-release阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_post_release', '[可选]')->options($branchArr['post-release'])->placeholder('输入部署主机发布后执行的任务(多个任务用|分割)');
            $form->text('customize_post_release', '[自定义]')->placeholder('输入自定义执行的任务(多个任务用|分割)');

        })->tab('post-deploy阶段任务', function ($form) {
            $branchArr = config('deployment.deploy_config');
            $form->checkbox('config_post_deploy', '[可选]')->options($branchArr['post-deploy'])->placeholder('输入部署后执行的任务(多个任务用|分割)');
            $form->text('customize_post_deploy', '[自定义]')->placeholder('输入自定义执行的任务(多个任务用|分割)');
        });

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
            $id = $form->model()->id; // 获取对应模型的id
//            echo "<pre>";
//            var_dump($form, $id);
//            die;
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
