<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\DeploymentTask;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class GroupManageController extends Controller
{
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content) {
        return $content
            ->header('群组列表')
            ->description('')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content) {
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
    public function edit($id, Content $content) {
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
    public function create(Content $content) {
        return $content
            ->header('新增发单')
            ->description('task')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid() {
        $grid = new Grid(new DeploymentTask);
        $grid->model()->orderBy('id', 'desc');
        $grid->id('Id')->sortable();

        $grid->task_description('任务名称');
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
    protected function detail($id) {
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
}