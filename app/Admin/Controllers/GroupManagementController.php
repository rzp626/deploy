<?php

namespace App\Admin\Controllers;

use App\AdminGroup;
use App\AdminUser;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Auth\Database\Menu;
use Encore\Admin\Auth\Database\Role;


class GroupManagementController extends Controller
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
            ->header('群组列表')
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
            ->header('组详情')
            ->description('detail')
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
            ->header('编辑组')
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
            ->header('新增组')
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
        $grid = new Grid(new AdminGroup);

        $grid->id('组ID');
        $grid->group_name('组名称');
        $grid->group_action('组用途');
        $grid->created_at('创建时间');
        $grid->updated_at('修改时间');

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
        $show = new Show(AdminGroup::findOrFail($id));

        $show->id('组Id');
        $show->group_name('组名称');
        $show->group_action('组用途');
        $show->created_at('创建时间');
        $show->updated_at('修改时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminGroup);

        $form->text('group_name', '组名字');
        $form->text('group_action', '组用途');
        $form->multipleSelect('roles', trans('admin.roles'))->options(Role::all()->pluck('name', 'id'));

        return $form;
    }

    public function assign(Content $content)
    {
        $groupInfo = AdminGroup::getGroupInfo();
        $userInfo = AdminUser::getUserInfo();
        $data = [
            'groupInfo' => $groupInfo,
            'userInfo'  => $userInfo,
        ];

        // 获取组数据
        return $content
            ->header('组分配')
            ->description('assign')
        ->body(view('group.index', ['groupInfo' => json_encode($groupInfo), 'userInfo' => json_encode($userInfo)]));
    }
}
