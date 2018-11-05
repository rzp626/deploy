<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/11/5
 * Time: 2:49 PM
 */

namespace App\Admin\Extensions;

use Encore\Admin\Admin;


class CheckRow
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT
$('.grid-check-row').on('click', function() {
    alert($(this).data('id'));
    // 添加ajax请求
});
SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a class='btn btn-xs btn-success fa fa-check grid-check-row' data-id='{$this->id}'>test</a>";
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->render();
    }
}