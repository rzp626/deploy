<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UmStrategy extends Model
{
    protected $table = 'um_strategy';
    protected static $strategyInfo = [];

    protected $casts = [
        'rule' => 'json',
    ];

    public static function getStrategyInfo()
    {
        if (!empty(self::$strategyInfo))
            return self::$strategyInfo;

        $tmp = self::select('id', 'name')->get();
        foreach ($tmp as $key => $value) {
            self::$strategyInfo[$value->id] = $value->name;
        }

        return  self::$strategyInfo;
    }
}
