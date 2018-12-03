<?php

namespace App\Admin\Controllers;

use App\AdminGroupUser;
use App\AdminGroup;
use App\AdminUser;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Log;
use Illuminate\Support\MessageBag;

class GroupUserManagementController extends Controller
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
            ->header('列表页')
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
            ->header('详情页')
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
            ->header('编辑页')
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
            ->header('新建页')
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
        $grid = new Grid(new AdminGroupUser);
        $groupInfo = AdminGroup::getGroupOptions();
        $grid->id('序号ID');
        $grid->group_id('组ID')->display(function ($group_id) use ($groupInfo) {
            if (isset($groupInfo[$group_id])) {
                return $groupInfo[$group_id];
            }

            return '';
        });

        $userInfo = AdminUser::getUserOptions();
        $grid->user_id('用户ID')->display(function ($user_id) use ($userInfo) {
            if (isset($userInfo[$user_id])) {
                return $userInfo[$user_id];
            }

            return '';
        });
        $grid->created_at('创建时间');

        $grid->actions(function (Grid\Displayers\Actions $actions) {
//            $actions->disableView();
            $actions->disableDelete();
            $actions->disableEdit();
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
        $show = new Show(AdminGroupUser::findOrFail($id));

        $show->id('序号ID');
        $show->group_id('组ID');
        $show->user_id('用户ID');
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
        $form = new Form(new AdminGroupUser);
        $groupOptions = AdminGroup::getGroupOptions();
        $userOptions = AdminUser::getUserOptions();
        $form->select('group_id', '组ID')->options($groupOptions);
        $form->select('user_id', '用户ID')->options($userOptions);

        $groupUserOptions = AdminGroupUser::getUserOptions();
        $form->saving(function (Form $form) use ($groupUserOptions) {
            $group_id = $form->input('group_id');
            $user_id = $form->input('user_id');
            if (isset($groupUserOptions)
                && !empty($groupUserOptions)
                && isset($groupUserOptions['group_id'])
                && isset($groupUserOptions['user_id'])) {
                if (in_array($group_id, $groupUserOptions['group_id']) && in_array($user_id, $groupUserOptions['user_id'])) {
                    Log::info('the user has been belong to the group, please check it.');
                    $error = new MessageBag([
                        'title' => '添加失败',
                        'message' => '该用户已在此组',
                    ]);

                    return back()->with(compact('error'));
                }
            }
        });

        $form->saved(function (Form $form) {

        });
        return $form;
    }
}
