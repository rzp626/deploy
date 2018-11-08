<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\DeploymentConfig;
use DB;

class DeploymentTask extends Model
{
    //max id
    protected static $mid;

    public function deploy_config()
    {
        return $this->hasOne('App\DeploymentConfig', 'id', 'task_name');
    }

//    public function setTaskEnvAttribute($value){
//        $this->attributes['task_env'] =trim(implode(",",$value),',');
//    }

    public static function getRelationInfo()
    {
        $options = self::select('id', 'task_name as text')->orderBy('id', 'desc')->get();
        $selection = [];
        foreach ($options as $k => $v) {
            $selection[$v->id]    = $v->text;
        }

        return $selection;
    }

    public static function getMaxId()
    {
        if (!isset(self::$mid)) {
            $data = self::select(DB::raw('max(id) as mid'))->get();
            foreach ($data as $k => $v) {
                self::$mid = $v->mid;
            }
        }

        return self::$mid;
    }
}