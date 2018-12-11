<?php

namespace App\Admin\Controllers;

use App\UmAction;
use App\Http\Controllers\Controller;
use App\UmGroup;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;

class UmActionController extends Controller
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
        return $content
            ->header('报警动作')
            ->description('list')
            ->body($this->grid());
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
            ->header('报警动作详情')
            ->description('show')
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
            ->header('编辑报警动作')
            ->description('edit')
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
            ->header('添加动作')
            ->description('create')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UmAction);

        $grid->id('动作ID');
        $grid->name('动作名称');
        $grid->explain('动作说明');
        $grid->notify_type_bit('通知方式')->display(function ($notify_type_bit) {
            return "<span class='label label-success'>$notify_type_bit</span>";
        });
        $grid->invalid_start_time('失效开始时间');
        $grid->invalid_end_time('失效结束时间');
        $grid->group_id('组ID');
        $grid->created_at('创建时间');

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
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(UmAction::findOrFail($id));

        $show->id('动作ID');
        $show->name('动作名称');
        $show->explain('动作说明');
        $show->notify_type_bit('通知方式');
        $show->invalid_start_time('失效开始时间');
        $show->invalid_end_time('失效结束时间');
        $show->group_id('组ID');
        $show->created_at('创建时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UmAction);

        $notifyArr = config('params.notify_way');

        $method = request()->route()->getActionMethod();
        if ($method == 'create' || $method == 'store') {
            $form->text('name', '动作名称')->rules('required|min:2');
            $actionInfo = UmAction::getActionInfo();
        } else if ($method == 'update' || $method == 'edit') {
            $form->text('name', '动作名称')->rules('required|min:2')->readOnly();
            $actionInfo = [];
        }

        $form->text('explain', '动作说明')->rules('required|min:2');
        $form->datetime('invalid_start_time', '失效开始时间')->default('');
        $form->datetime('invalid_end_time', '失效结束时间')->default('');
        $form->multipleSelect('notify_type_bit', '通知方式')->options($notifyArr)->rules('required|min:1');
        $form->select('group_id', '组ID')->options(UmGroup::all()->pluck('name', 'id'))->rules('required|min:1');

        $form->saving(function (Form $form) use ($actionInfo, $method) {
            $actionName = $form->input('name');
            if (in_array($actionName, $actionInfo)) {
                $error = new MessageBag([
                    'title' => '该动作名称已存在',
                    'message' => '请添加新的动作名',
                ]);

                return back()->with(compact('error'));
            }
        });

        $form->savd(function (Form $form) {

        });

        return $form;
    }
}
