<?php

namespace App\Admin\Controllers;

use App\DeploymentTask;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use DB;

class CustomConfigController extends Controller {
	use HasResourceActions;

	/**
	 * Index interface.
	 *
	 * @param Content $content
	 * @return Content
	 */
	public function index(Content $content) {
		// 先将配置文件，读取，放置到view上，在对其进行更改，保存操作即可生成新的配置文件
        $config = config('review');
		return $content
			->header('邮件配置')
			->description('')
			// ->body($this->grid());
			->body(view('mails.test', $config));
	}

	/**
	 * Make a grid builder.
	 *
	 * @return Grid
	 */
	protected function grid() {
		$grid = new Grid(new DeploymentTask());
		$grid->id('Id');
		$grid->actions(function (Grid\Displayers\Actions $actions) {
			$actions->disableView();
			$actions->disableDelete();
			$actions->disableEdit();
		});

		$grid->tools(function ($tools) {
			$tools->batch(function ($batch) {
				$batch->disableDelete();
			});
		});

		$grid->filter(function ($filter) {
			// $filter->between('created_at', '发单日期')->datetime();
		});

		return $grid;
	}

    /**
     * Add interface.
     *
     * @param Content $content
     * @return Content
     */
	public function add(Content $content)
    {
        // 先将配置文件，读取，放置到view上，在对其进行更改，保存操作即可生成新的配置文件
        $config = config('review');
		return $content
			->header('创建/修改配置文件')
			->description('')
			// ->body($this->grid());
			->body(view('config.index'));
    }

    public function showMessage(Content $content)
    {
        return $content
            ->header('')
            ->description('')
            ->body(view('messageBoard.add'));
    }

    public function listMessage(Content $content)
    {
        $data = DB::table('messages')->select('name', 'content', 'created_at')->orderBy('created_at', 'desc')->get();
        return $content
            ->header('')
            ->description('')
            ->body(view('messageBoard.list')->with('data', $data));
    }
}
