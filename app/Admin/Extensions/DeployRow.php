<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/11/5
 * Time: 2:49 PM
 */

namespace App\Admin\Extensions;

use Encore\Admin\Admin;


class DeployRow
{
    protected $id;

    // 区分操作方式： deploy-发布 / rollback-回滚
    protected $type;

    protected $link;

    public function __construct($id, $type, $link)
    {
        $this->id = $id;
        $this->type = $type;
        $this->link = $link;
    }

    protected function script()
    {
        if ($this->type == 'deploy') {
            $url = 'deploy';
            $info = '发布进行中...';
        } else if ($this->type == 'rollback') {
            $url = 'rollback';
            $info = '回滚中...';
        }

        return <<<SCRIPT
var ele = '.grid-check-row-'+'$this->id';
//console.log(ele);
$(ele).on('click', function() {
//    alert($(this).data('id'));
//    console.log($(this).data('id'));
    // 添加ajax请求
    $.ajax({
        type: 'post',
        url: '$url',
        dataType: 'json',
        contentType: 'application/x-www-form-urlencoded',
        data: {'id': '$this->id', '_token': LA.token},
        async: true,
        cache: false,
        beforeSend: function () {
            $.LoadingOverlay("show", { 
                text: '$info', 
                image: "",
                fontawesome : "fa fa-cog fa-spin",
                background: "rgba(165, 190, 100, 0.5)",
            });
        },
        success: function(data) {
            $.LoadingOverlay("hide", true);
//            console.log(data.code);
//            console.log(data.msg);
//            console.log(data.page);
            if (typeof data.code != undefined && data.code == 200) { // 成功了
                 alert(data.msg);
            } else if (typeof data.code != undefined && data.code == 400) { // 失败了
                 alert(data.msg);
            }         
            // 页面跳转
            window.location.href="/page?data="+data.page; 
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

        return $this->link;
//        return "<a class='btn btn-xs btn-success fa fa-check grid-check-row-$this->id' data-id='{$this->id}'>test</a>";
    }

    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->render();
    }
}