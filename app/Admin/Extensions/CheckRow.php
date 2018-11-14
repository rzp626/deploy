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
var ele = '.grid-check-row-'+$this->id;
$(ele).on('click', function() {
    console.log($(this).data('id'));
    // 添加ajax请求
    $.ajax({
        type: 'post',
        url: 'test',
        dataType: 'json',
        contentType: 'application/x-www-form-urlencoded',
        data: {'id': $this->id, '_token': LA.token},
        async: true,
        cache: false,
        beforeSend: function () {
            $.LoadingOverlay("show", { text: "发布进行中...",});
//            toastr.warning("发布进行中...");  
            $(ele).removeAttr('onclick');
        },
        success: function(data) {
            $.LoadingOverlay("hide");
            console.log(data);
        },
        error: function(data) {
            alert(data);
        }
    });
});
SCRIPT;
    }



    protected function render()
    {
        Admin::script($this->script());

        return "<a class='btn btn-xs btn-success fa fa-check grid-check-row-$this->id' data-id='{$this->id}'>test</a>";
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->render();
    }
}