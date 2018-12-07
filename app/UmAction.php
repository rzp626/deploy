<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UmAction extends Model
{
    protected $table = 'um_action';
    protected static $actionInfo = [];

//    public function setGroupIdAttribute($value)
//    {
//        $this->attributes['group_id'] = trim(implode(',', $value));
//    }

    public function getNotifyTypeBitAttribute($value)
    {
        $info = config('params.notify_way');
        $len = strlen($value);
        $baseNum = base_convert($value, 2, 10);

        $str = '';
        if ($len == 1) {
            $str = $info[$len];
        } else if ($len >= 2) {
            $j = 0;
            $value = strrev($value);
            for ($i = 0; $i < $len; $i++) {
                $num = pow(2, $j);
                if (isset($value[$i]) && !empty($value[$i])) {
                    $str .= $info[$value[$i] * $num] . ',';
                }
                $j++;
            }
        }
        $str = rtrim($str, ',');
        return $str;
    }

    public function setNotifyTypeBitAttribute($value)
    {
        if (array_filter($value)) {
            $value = decbin(array_sum(array_filter($value)));
        }
        $this->attributes['notify_type_bit'] = $value;
    }

    public static function getActionInfo()
    {
        if (!empty(self::$actionInfo))
            return self::$actionInfo;

        $tmp = self::select('id', 'name')->get();
        foreach ($tmp as $key => $value) {
            self::$actionInfo[$value->id] = $value->name;
        }

        return  self::$actionInfo;
    }
}
