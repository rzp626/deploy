<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class AdminGroup extends Model
{
    protected $table = 'admin_groups';
    public static $groupInfo = [];

    public static function getGroupInfo()
    {
        $arr = [];
        $info = [];
        $arr = self::select('id', 'group_name', 'group_action')->get();
        Log::info('the group info '.print_r($arr, true));
        if (!isset($arr) || empty($arr))
            return $arr;

        $i = 0;
        foreach ($arr as $key => $value) {
            $info[$i]['id'] = $value->id;
            $info[$i]['name'] = $value->group_name;
            $info[$i]['action'] = $value->group_action;
            $i++;
        }
        return $info;
    }

    public static function getGroupOptions()
    {
        if (!empty(self::$groupInfo)) {
            return self::$groupInfo;
        }

        $arr = self::select('id', 'group_name')->get();
        Log::info('the group info '.print_r($arr, true));
        if (!isset($arr) || empty($arr))
            return $arr;

        $i = 0;
        foreach ($arr as $key => $value) {
            self::$groupInfo[$value->id] = $value->group_name;
        }

        return self::$groupInfo;
    }
}
