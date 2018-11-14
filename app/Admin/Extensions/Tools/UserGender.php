<?php
/**
 * Created by PhpStorm.
 * User: zhenpeng8
 * Date: 2018/11/5
 * Time: 3:01 PM
 */

namespace App\Admin\Extensions\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Tools\AbstractTool;
use Illuminate\Support\Facades\Request;

class UserGender extends AbstractTool
{
    protected function script()
    {
        $url = Request::fullUrlWithQuery(['gender' => '_gender_']);
        return <<<EOT
$('input:radio.user-gender').change(function () {
    var url = "$url".replace('_gender_', $(this).val());
    $.pjax({container:'#pjax-container', url: url});
});
EOT;
    }

    public function render()
    {
        // TODO: Implement render() method.
        Admin::script($this->script());

        $options = [
            'all' => 'All',
            'm'   => 'Male',
            'f'   => 'Female',
        ];

        return view('admin.tools.gender', compact('options'));
    }
}