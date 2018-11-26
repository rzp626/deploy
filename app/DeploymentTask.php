<?php

namespace App;

use function Couchbase\defaultDecoder;
use Illuminate\Database\Eloquent\Model;
use App\DeploymentConfig;
use DB;

class DeploymentTask extends Model
{
    //max id
    protected static $mid;
    protected static $userInfo = null;

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

    
    /**
    * 获取审批用户id
    *
    * @return array
    */
    public static function getUserInfo()
    {
        if (!empty(self::$userInfo)) {
            return self::$userInfo;
        }

        $tmpInfo = DB::table('admin_users')->select('id', 'username', 'name')->get();
        $i = 0;
        foreach ($tmpInfo as $user) {
            self::$userInfo[$user->id] = $user->name;
        }

        return self::$userInfo;
    }

    /**
     * 获取今日审核/发单数量
     *
     * @param int $type
     * @return int
     */
    public static function getReviewSum()
    {
        $startTime = date("Y-m-d", time())." 00:00:00";
        $stopTime = date("Y-m-d", time())." 23:59:59";

        $ret = [];
        $deployRet = self::select(DB::raw('count(*) as sum'))->whereBetween('created_at', [$startTime, $stopTime])->get();
        $reviewRet = self::select(DB::raw('count(*) as sum'))->whereBetween('updated_at', [$startTime, $stopTime])->get();
        if (!empty($deployRet)) {
            $ret['deployNum'] = $deployRet[0]->sum;

        }

        if (!empty($reviewRet)) {
            $ret['reviewNum'] = $reviewRet[0]->sum;

        }

        if (empty($ret)) {
            Log::info('Query failed, please check it!');
        }

        return $ret;
    }
}