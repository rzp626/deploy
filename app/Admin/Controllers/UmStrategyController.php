<?php

namespace App\Admin\Controllers;

use App\UmStrategy;
use App\UmAction;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class UmStrategyController extends Controller
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
            ->header('策略列表')
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
            ->header('策略详情')
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
            ->header('编辑策略')
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
            ->header('添加策略')
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
        $grid = new Grid(new UmStrategy);

        $grid->id('策略ID');
        $grid->name('策略名称');
        $grid->explain('策略说明');
        $grid->rule('报警规则')->display(function ($rule) {
            $rule = json_encode($rule);
            return "<span class='label label-success'>$rule</span>";
        });
        $grid->cycle_times_x('周期次数x值');
        $grid->cycle_times_y('周期次数y值');
        $grid->level('报警级别');
        $grid->action_id('报警动作ID');
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
        $show = new Show(UmStrategy::findOrFail($id));

        $show->id('策略ID');
        $show->name('策略名称');
        $show->explain('策略说明');
        $show->rule('报警规则');
        $show->cycle_times_x('周期次数x值');
        $show->cycle_times_y('周期次数y值');
        $show->level('报警级别');
        $show->action_id('报警动作ID');
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
        $form = new Form(new UmStrategy);
        $levelArr = config('params.alert_level');
        $operatorArr = config('params.operator');
        $aggregateArr = config('params.aggregate');
        $method = request()->route()->getActionMethod();

        if ($method == 'create' || $method == 'store') {
            $form->text('name', '策略名称')->rules('required|min:2');
            $strategyInfo = UmStrategy::getStrategyInfo();
        } else if ($method == 'edit' || $method == 'update') {
            $form->text('name', '策略名称')->rules('required|min:2')->readOnly();
            $strategyInfo = [];
        }

        $form->text('explain', '策略说明')->rules('required|min:2');
//        $form->text('rule', '报警规则')->rules('required|min:2');


        $form->embeds('rule', '报警规则', function ($form) use ($operatorArr, $aggregateArr) {
            $form->text('ex_query', '查询条件')->rules('required|min:1');
            $form->select('aggregate', '聚合条件')->options($aggregateArr)->rules('required|min:1');
            $form->select('operator', '操作符号')->options($operatorArr)->rules('required|min:1');
            $form->number('threshold', '阈值')->rules('required|min:1');
        });

        $form->number('cycle_times_x', '周期次数x值')->rules('required|integer');
        $form->number('cycle_times_y', '周期次数y值')->rules('required|integer');
        $form->select('level', '报警级别')->options($levelArr)->rules('required|integer');
        $form->select('action_id', '报警动作ID')->options(UmAction::all()->pluck('name', 'id'))->rules('required|min:1');

        $form->saving(function (Form $form) use ($strategyInfo, $operatorArr, $aggregateArr) {
            $strategyName = $form->input('name');
            if (in_array($strategyName, $strategyInfo)) {
                $error = new MessageBag([
                    'title' => '该策略名称已存在',
                    'message' => '请添加新的策略名',
                ]);

                return back()->with(compact('error'));
            }

            $ruleArr = $form->input('rule');
            $tmp = [];
            foreach ($ruleArr as $key => $value) {
                if ($key == 'aggregate')
                    $value = $aggregateArr[$value];

                if ($key == 'operator')
                    $value = $operatorArr[$value];

                $tmp[$key] = $value;
            }
            $form->input('rule', $tmp);
        });

        $form->saved(function (Form $form) {

        });
        return $form;
    }
}
