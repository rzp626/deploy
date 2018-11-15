<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\DeployRow;
use App\DeploymentConfig;
use App\DeploymentTask;
use App\Http\Controllers\Controller;
use App\Services\UtilsService;
use Encore\Admin\Admin;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use App\Services\CMailFileService;
use App\Jobs\ReviewEmailJob;

class DeploymentTaskController extends Controller
{
    use HasResourceActions;

	private $user;

	/**
	 * DeploymentConfigController constructor.
	 */
	public function __construct() {
		$this->user = new Admin();
	}

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
	public function index(Content $content) {
		return $content
			->header('工单列表')
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
		$user = new Admin();
		$grid->model()->orderBy('id', 'desc');
		$grid->id('Id')->sortable();
		$grid->config_id('项目名称')->display(function ($config_id) {
			$info = DeploymentConfig::where('id', $config_id)->select('config_name')->first();
			if (isset($info)) {
				return $info->config_name;
			}
			return $config_id;
		});

		$grid->task_description('任务名称');
		$grid->task_env('发布环境')->display(function ($task_env) {
			$envArr = config('deployment.deploy_config.task_env');
			if (isset($envArr[$task_env])) {
				return $envArr[$task_env] . '环境';
			}
		});

		$grid->task_branch('选取分支')->display(function ($task_branch) {
			$branchArr = config('deployment.deploy_config.task_branch');
			if (isset($branchArr[$task_branch])) {
				return $branchArr[$task_branch] . '分支';
			}
		});

		$grid->created_at('上线时间');

		$grid->actions(function (Grid\Displayers\Actions $actions) {
			$actions->disableView();
			$actions->disableDelete();
			$actions->disableEdit();

			$review_status = $actions->row->review_status;
			if ($review_status == 0) {
				// 审核中
				$actions->append('<span class="btn btn-xs btn-danger">审核中</span>');
				// 可以加一个邮件催促提醒审核过程
			} else if ($review_status == 1) {
				// 审核未通过
				$actions->append('<span class="btn btn-xs btn-danger">审核未通过，请重新发单</span>');
			} else if ($review_status == 2) {
				// 审核通过
				$status = $actions->row->task_status;
				$taskId = $actions->row->id;
				$env_id = $actions->row->task_env;
				$branch_id = $actions->row->task_branch;
				$config_id = $actions->row->config_id;
				$id = $taskId . '-' . $env_id . '-' . $branch_id . '-' . $config_id;
				$releaseId = $actions->row->release_id;
				$rollbackId = $id . '-' . $releaseId;
				$releaseStatus = $actions->row->release_status;
				$info = '<a href="/admin/dp/log/?id=' . $releaseId . '" class="btn btn-xs btn-info">执行日志</a>&nbsp;&nbsp;&nbsp;&nbsp;';
				$rollbackLink = "<a class='btn btn-xs btn-primary grid-refresh grid-check-row-{$rollbackId}' data-id='{$rollbackId}'><i class='fa fa-refresh'></i> 回滚</a>";

				if ($status == 0) {
                    $url = "<a class='btn btn-xs btn-info grid-check-row-{$id}' data-id='{$id}'>点击发布</a>";
                    $actions->append(new DeployRow($id, 'deploy', $url));
                } else if ($status == 1) {
                    // 发布进行中
                    $aLink = '<span class="btn btn-xs btn-info">发布进行中</span>';
                    $actions->append($aLink);
				} else if ($status == 2) {
					$actions->append($info); // 看执行log
					if ($releaseStatus == 1) {
						// 回滚成功 == 已回滚
						$aLink = '<span class="btn btn-xs btn-warning">回滚成功</span>';
						$actions->append($aLink);
					} else if ($releaseStatus == 2) {
                        // 回滚失败
                        $aLink = '<span class="btn btn-xs btn-danger">回滚失败</span>';
                        $actions->append($aLink);
                    } else if ($releaseStatus == 3) {
					    // 回滚中
                        $aLink = '<span class="btn btn-xs btn-info">回滚中</span>';
                        $actions->append($aLink);
					} else if ($releaseStatus == 0) {
						$maxId = DeploymentTask::getMaxId();
						if ($taskId == $maxId) {
							$aLink = '<span class="btn btn-xs btn-success">发布成功</span>';
							$actions->append($aLink);
						} else {
							$aLink = '<span class="btn btn-xs btn-success">发布成功</span>&nbsp;&nbsp;&nbsp;&nbsp;';
							$actions->append($aLink);
							$actions->append(new DeployRow($rollbackId, 'rollback', $rollbackLink));
						}
					}
				} else if ($status == 3) {
					$url = "<span class='btn btn-xs btn-danger'>发布失败</span>";
					$actions->append($info . $url);
				}
			}

		});

		$grid->operator('操作人');

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

	/**
	 * Make a form builder.
	 *
	 * @return Form
	 */
	protected function form($action = 'create') {
		$form = new Form(new DeploymentTask);

		$form->select('config_id', '项目名')->options('/admin/dp/config')->load('task_env', '/admin/dp/env');
		$form->text('task_description', '任务名称')->rules('required|min:1');
		$form->select('task_env', '部署环境')->load('task_branch', '/admin/dp/branch');
		$form->select('task_branch', '选取分支');
		$form->hidden('task_status')->default(0);
		$form->hidden('operator')->default('');
		$form->hidden('review_group_member')->default(0);
		$form->hidden('review_status')->default(0);

		$username = $this->user->user()->username;
		if ($username) {
			Log::info('the operator is : ' . $username . ' The time is ' . time());
			$form->input('operator', $username);
		}
		// 如果当前用户具有审核权限，则直接审核通过。。。
		if (Permission::check('review')) {
			$form->input('review_group_member', $this->user->user()->id);
			$form->input('review_status', 2);
		} else {
			$form->select('review_group_member', '审核人')->options('/admin/members');
			$form->input('review_status', 0);
		}

		$form->saving(function (Form $form) {
			try {
				$error = [];
				if (empty($form->input('config_id')) || empty($form->input('review_group_member'))) {
					$error = new MessageBag([
						'title' => '操作失败',
						'message' => '检查所填写的参数',
					]);
				}
				$envStr = $form->input('task_env');
				$envArr = explode('-', $envStr);
				$form->input('task_env', $envArr[1]);
//				$form->review_status = 2;
				Log::info('add task info ' . json_encode($form->input(null)));
			} catch (Exception $e) {
				Log::info('exception occered,' . $e->getMessage() . ' please check it.');
				$error = [
					'title' => '新增失败',
					'message' => $e->getMessage(),
				];
			}

			if (!empty($error)) {
				return back()->with(compact('error'));
			}
		});

		$form->saved(function (Form $form) {
			// 获取选择的分支、id等，进行操作,增加重试机制retry_times = 3
            $user = $form->model()->operator;
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

			$this->dispatch(new ReviewEmailJob($user, 'deploy'));
		});

		return $form;
	}
}
