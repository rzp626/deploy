<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class AdminUser extends Model
{
    protected $table = 'admin_users';

    public static function getUserInfo($type=0)
    {
        $arr = [];
        $info = [];
        $arr = self::select('id', 'name')->get();
        Log::info('the group info '.print_r($arr, true));
        if (!isset($arr) || empty($arr))
            return $arr;

        $i = 0;
        if ($type > 0) {
            foreach ($arr as $key => $value) {
                $info[$i]['id'] = $value->id;
                $info[$i]['name'] = urlencode($value->name);
                $i++;
            }
            return $info;
        } else {
            foreach ($arr as $key => $value) {
                $info[$i]['id'] = $value->id;
                $info[$i]['name'] = $value->name;
                $i++;
            }
        }
        return $info;
    }
}
