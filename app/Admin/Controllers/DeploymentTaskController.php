<?php

namespace App\Admin\Controllers;

use App\DeploymentTask;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use function foo\func;
use Illuminate\Support\MessageBag;
use App\Services\UtilsService;
use App\DeploymentConfig;

class DeploymentTaskController extends Controller
{
    use HasResourceActions;

    protected $orderDefault = [
//        'created_id' => 'desc',
        'id' => 'desc',
    ];

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('部署任务')
            ->description('列表')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('任务详情')
            ->description('查看该任务详情')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('编辑')
            ->description('编辑任务')
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
            ->header('新增任务')
            ->description('新增部署任务')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DeploymentTask);
        $grid->model()->orderBy('id', 'desc');
        $grid->id('Id')->sortable();
        $grid->config_id('项目名称')->display(function ($config_id) {
            $info = DeploymentConfig::where('id', $config_id)->select('config_name')->first();
            if (isset($info)) {
                return  $info->config_name;
            }
            return $config_id;
        });

        $grid->task_description('任务名称');
        $grid->task_env('发布环境')->display(function ($task_env) {
            $envArr = config('deployment.deploy_config.task_env');
            if (isset($envArr[$task_env])) {
                return $envArr[$task_env].'环境';
            }
        });

        $grid->task_branch('选取分支')->display(function ($task_branch) {
            $branchArr = config('deployment.deploy_config.task_branch');
            if (isset($branchArr[$task_branch])) {
                return $branchArr[$task_branch].'分支';
            }
        });

        $grid->created_at('上线时间');

        $grid->actions(function (Grid\Displayers\Actions $actions) {

            $status = $actions->row->task_status;
            $taskId = $actions->row->id;
            $env_id = $actions->row->task_env;
            $branch_id = $actions->row->task_branch;
            $config_id = $actions->row->config_id;
            $id = $taskId.'-'.$env_id.'-'.$branch_id.'-'.$config_id;
            $releaseId = $actions->row->release_id;
            $rollbackId = $id.'-'.$releaseId;
            $releaseStatus = $actions->row->release_status;
            $aLink = '';
            $info = '<a href="/admin/log/'.$releaseId.'" class="btn btn-xs btn-info">执行日志</a>&nbsp;&nbsp;&nbsp;&nbsp;';
            if ($releaseStatus == 1) { // 回滚成功 == 已回滚
                $aLink = '<span class="btn btn-xs btn-warning">回滚成功</span>';
            } else if ($releaseStatus == 2) { // 回滚失败
                $aLink = '<span class="btn btn-xs btn-danger">回滚失败</span>';
            } else { // 初始状态
                $maxId = DeploymentTask::getMaxId();
                if ($taskId == $maxId) {
                    $aLink = '<span class="btn btn-xs btn-success">发布成功</span>';
                } else {
                    $aLink = '<span class="btn btn-xs btn-success">发布成功</span>&nbsp;&nbsp;&nbsp;&nbsp; <a  href="/admin/rollback/'.$rollbackId.'" class="btn btn-xs btn-primary grid-refresh"><i class="fa fa-refresh"></i> 回滚</a>';
                }
            }


            if ($status == 1) {
                $actions->disableView();
                $actions->disableDelete();
                $actions->disableEdit();
                $url = "<a href='/admin/deploy/$id' class='btn btn-xs btn-info'>点击发布</a>";
                $actions->append($url);
            } else if ($status ==2 ){
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
                $actions->append($info.$aLink);
            } else if ($status == 3) {
                $actions->disableView();
                $actions->disableDelete();
                $actions->disableEdit();
                $url = "<span class='btn btn-xs btn-danger'>发布失败</span>";
                $actions->append($info.$url);
            }
        });

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->filter(function ($filter) {
            $filter->between('created_at', '创建日期')->datetime();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(DeploymentTask::findOrFail($id));

        $show->id('任务Id');
        $show->config_id('任务名称');
        $show->task_description('任务描述');
        $show->task_branch('分布分支');
        $show->task_env('分布环境');
        $show->task_status('任务状态');
        $show->created_at('创建日期');
        $show->updated_at('修改日期');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($action='create')
    {
        $form = new Form(new DeploymentTask);

        $form->select('config_id', '项目名')->options('/admin/config')->load('task_env', '/admin/env');
        $form->text('task_description', '任务名称')->rules('required|min:3');
        $form->select('task_env', '部署环境')->load('task_branch', '/admin/branch');
        $form->select('task_branch', '选取分支');
        $form->hidden('task_status')->default(1);

        $form->footer(function ($footer) {
            // 去掉`重置`按钮
//            $footer->disableReset();
            // 去掉`提交`按钮
//            $footer->disableSubmit();
        });

        $form->saving(function (Form $form) {
            $error = [];
            if (empty($form->input('config_id'))) {
                $error = new MessageBag([
                    'title' => '操作失败',
                    'message' => '检查所填写的参数',
                ]);
            }
            $envStr = $form->input('task_env');
            $envArr = explode('-', $envStr);
            $form->input('task_env', $envArr[1]);

            if (!empty($error)) {
                return back()->with(compact('error'));
            }
        });

        $form->saved(function (Form $form) {
            // 获取选择的分支、id等，进行操作,增加重试机制retry_times = 3
            $taskInfo = [
                'task_id' => $form->model()->id,
                'task_branch' => $form->model()->task_branch,
                'task_env' => $form->model()->task_env,
            ];
            $retry_time = 3;
            while ($retry_time) {
                $returnRes = UtilsService::changeConfigFunc($taskInfo);
                if ($returnRes) {
                    break;
                }
                $retry_time--;
            }

            /*return back()->with(compact('success'));
            // 返回一个简单response
            return response('xxxx');
            // 跳转页面
            return redirect('/admin/users');
            // 抛出异常
            throw new \Exception('出错啦。。。');*/
        });

        return $form;
    }
}
