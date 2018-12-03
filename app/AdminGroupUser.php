<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class AdminGroupUser extends Model
{
    protected $table = 'admin_group_users';

    public static function getUserOptions()
    {
        $info = [];
        $arr = self::select('group_id', 'user_id')->get();
        Log::info('the group info '.print_r($arr, true));
        if (!isset($arr) || empty($arr))
            return $arr;

        foreach ($arr as $key => $value) {
            $info['group_id'][] = $value->group_id;
            $info['user_id'][] = $value->user_id;
        }

        return $info;
    }
}
