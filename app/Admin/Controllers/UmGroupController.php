<?php

namespace App\Admin\Controllers;

use App\AdminUser;
use App\UmGroup;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;

class UmGroupController extends Controller
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
            ->header('报警组列表')
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
            ->header('报警组详情')
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
            ->header('编辑报警组')
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
            ->header('添加报警组')
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
        $grid = new Grid(new UmGroup);
        $grid->model()->orderBy('id', 'desc');
        $grid->id('组ID')->sortable();
        $grid->name('组名字');
        $grid->explain('组描述');
        $grid->members('组成员');
        $grid->created_at('创建时间');

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        $grid->filter(function ($filter) {
            $filter->between('created_at', '创建日期')->datetime();
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
        $show = new Show(UmGroup::findOrFail($id));

        $show->id('组ID');
        $show->name('组名称');
        $show->explain('组描述');
        $show->members('组成员');
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
        $form = new Form(new UmGroup);

        $groupInfo = [];
        $method = request()->route()->getActionMethod();
        if ($method == 'create' || $method == 'store') {
            $form->text('name', '名称')->rules('required|min:3');
            $groupInfo = UmGroup::getGroupInfo();
        } else if ($method == 'edit' || $method == 'update') {
            $form->text('name', '名称')->rules('required|min:3')->readOnly();
        }

        $form->text('explain', '描述')->rules('required|min:2');
        $form->multipleSelect('members', '成员列表')->options(AdminUser::all()->pluck('name', 'name'))->rules('required|min:1');

        $form->saving(function (Form $form) use ($groupInfo) {
            $groupName = $form->input('name');
            if (in_array($groupName, $groupInfo)) {
                $error = new MessageBag([
                    'title' => '该组名称已存在',
                    'message' => '请添加新的组名',
                ]);

                return back()->with(compact('error'));
            }

            $members = $form->input('members');
            $cnt = count($members);
            if ($cnt == 1) {
                $error = new MessageBag([
                    'title' => '成员必添',
                    'message' => '请添加组成员',
                ]);

                return back()->with(compact('error'));
            } else {
                $members = array_filter($members);
                $strMembers = implode(',', $members);
                $form->input('members', $strMembers);
            }
        });

        $form->saved(function (Form $form) {

        });
        return $form;
    }
}
