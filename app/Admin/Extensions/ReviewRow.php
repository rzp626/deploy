<?php 

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class ReviewRow
{
	/**
	* @var 任务id
	*/
    protected $id;

    protected $link;

    public function __construct($id, $link)
    {
        $this->id = $id;
        $this->link = $link;
    }

    protected function script()
    {
    	$url = '/admin/change_review_status';
        return <<<SCRIPT
var ele = '.grid-check-row-'+'$this->id';
$(ele).on('click', function() {
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
                text: '审批中', 
                image: "",
                fontawesome : "fa fa-cog fa-spin",
                background: "rgba(165, 190, 100, 0.5)",
            });
        },
        success: function(data) {
            $.LoadingOverlay("hide", true);
            console.log(data);
            if (typeof data.code != undefined && data.code == 200) { // 审核通过
                 alert(data.msg);
            } else if (typeof data.code != undefined && data.code == 400) { // 参数有误
                 alert(data.msg);
             } else if (typeof data.code != undefined && data.code == 401) { // 修改审批状态失败
                 alert(data.msg);
            } else {
                location.reload(true);
                return true;
            }        
            location.reload(true);
        },
        error: function(data) {
            $.LoadingOverlay("hide", true);
            alert(data);
        },
    });
});
SCRIPT;
    }



    protected function render()
    {
        Admin::script($this->script());

        return $this->link;
    }

    public function __toString()
    {
        return $this->render();
    }	
}