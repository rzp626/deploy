<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UmGroup extends Model
{
    protected $table = 'um_group';
    protected static $groupInfo = [];

    public function setMembersAttribute($value)
    {
        $this->attributes['members'] = trim(implode(',', $value));
    }

    public static function getGroupInfo()
    {
        if (!empty(self::$groupInfo))
            return self::$groupInfo;
        $tmp = [];
        $tmp = self::select('id', 'name')->get();
        foreach ($tmp as $key => $value) {
            self::$groupInfo[$value->id] = $value->name;
        }

        return  self::$groupInfo;
    }
}
