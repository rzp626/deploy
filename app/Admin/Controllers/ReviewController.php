<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ReviewRow;
use App\DeploymentTask;
use App\Http\Controllers\Controller;
use DB;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Exception;
use Illuminate\Http\Request;
use Log;
use App\Services\UtilsService;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller {
	use HasResourceActions;

	/**
	 * Index interface.
	 *
	 * @param Content $content
	 * @return Content
	 */
	public function index(Content $content) {
		return $content
			->header('审核列表')
			->description('')
			->body($this->grid());
	}

	/**
	 * Make a grid builder.
	 *
	 * @return Grid
	 */
	protected function grid() {
		$grid = new Grid(new DeploymentTask());
		$grid->model()->orderBy('id', 'desc');
		$grid->id('工单号')->sortable();
		$grid->operator('发单人');
		$grid->created_at('发单时间');
		$grid->review_group_member('审核人')->display(function ($review_group_member) {
			$userInfo = DeploymentTask::getUserInfo();
			return $userInfo[$review_group_member] ?? '临时';
		});

		$grid->actions(function (Grid\Displayers\Actions $actions) {
			$actions->disableView();
			$actions->disableDelete();
			$actions->disableEdit();

			$taskId = $actions->row->id;
			$reviewStatus = $actions->row->review_status;
			if ($reviewStatus == 2) {
				// 审核通过
				$info = '<span class="btn btn-xs btn-info">审核已通过</span>';
				$actions->append($info);
			} else if ($reviewStatus == 1) {
				// 审核未通过
				$info = '<span class="btn btn-xs btn-danger">审核未通过</span>';
				$actions->append($info);
			} else if ($reviewStatus == 0) {
				// 待审核
				$aLink = "<a class='btn btn-xs btn-primary grid-refresh grid-check-row-{$taskId}' data-id='{$taskId}'><i class='fa fa-refresh'></i>待审批</a>";
				$actions->append(new ReviewRow($taskId, $aLink));
			}
		});

		$grid->tools(function ($tools) {
			$tools->batch(function ($batch) {
				$batch->disableDelete();
			});
		});

		$grid->filter(function ($filter) {
			$filter->between('created_at', '发单日期')->datetime();
		});

		return $grid;
	}

	/**
	 * 修改审核状态
	 *
	 * @return string
	 */
	public function changeReviewStatus(Request $request) {
		$taskId = $request->get('id');
		if (!isset($taskId) || empty($taskId)) {
			return response()->json([
				'code' => 400,
				'msg' => '参数有误，请检查',
			]);
		}

		try {
			$info = [];
			$taskModel = DeploymentTask::find($taskId);
			$taskModel->review_status = 2; // 审核通过
			$taskModel->save();
		} catch (Exception $e) {
			Log::info('the change review status is failed. the Exception info is ' . $e->getMessage());
			$info = [
				'code' => '401',
				'msg' => $e->getMessage(),
			];
		}

		if (empty($info)) {
			$info = [
				'code' => '200',
				'msg' => '审核通过',
			];
		}
        // 统计审核发单数量
        if (Cache::has('reviewTaskNum')) {
            Cache::increment('reviewTaskNum');
        } else {
            $endTime = strtotime(date('Y-m-d', time()).' 23:59:59');
            $lifeTime = round(($endTime - time()) / 60);
            Cache::put('reviewTaskNum', 1, $lifeTime);
        }

		return response()->json($info);
	}

	/**
	 * 获取审核组成员
	 *
	 * @return array
	 */
	public function getReviewers() {
		// 获取具有审批权限的用户列表
		$tmpInfo = DB::table('admin_roles as r')
			->leftjoin('admin_role_users as u', 'r.id', '=', 'u.role_id')
			->select('u.user_id as uid')
			->where('r.slug', 'administrator')
			->get();

		// 整理userid
		$arrIds = [];
		foreach ($tmpInfo as $info) {
			$arrIds[] = $info->uid;
		}

		// 根据uid查询用户名称
		$userObj = DB::table('admin_users')
			->select('id', 'username')
			->whereIn('id', $arrIds)
			->get();
		$i = 0;
		$emailArr = [];
		$userInfo = [];
		foreach ($userObj as $user) {
			$userInfo[$i]['id'] = $user->id;
			$userInfo[$i]['text'] = $user->username;
			$emailArr['email'][$user->id] = $user->username . '@staff.weibo.com';
			$i++;
		}

		$path = config_path() . '/review.php';
		if (!file_exists($path)) {
				UtilsService::saveConfig($path, $emailArr);
		} else {
			$config = include $path;
			if (isset($config) && !empty($config) && array_key_exists('email', $config)) {
                $flag = false;
                foreach ($emailArr as $key => $value) {
                    if ($key == 'email') {
                        foreach ($value as $k => $v) {
                            if (!isset($config[$key][$k])) {
                                $flag = true;
                            }
                        }
                    }
                }
                if ($flag) {
                    UtilsService::saveConfig($path, $emailArr);
                }
			}
		}

		return $userInfo;
	}
}
