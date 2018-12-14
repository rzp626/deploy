<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\DeploymentTask;

class DeploymentConfig extends Model
{
    //
    protected $table = 'deployment_config';

    protected $fillable = [
        'config_name',
        'config_env',
        'config_user',
        'config_from',
        'config_host_path',
        'config_releases',
        'config_exlude',
        'config_hosts',
        'config_pre_deploy',
        'config_on_deploy',
        'config_on_release',
        'config_post_release',
        'config_post_deploy',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['customize_pre_deploy', 'customize_on_deploy', 'customize_on_release', 'customize_post_release', 'customize_post_deploy'];


    public function setOperatorAttribute($value) {
        $this->attributes['operator'] = $value;
    }

    public function setConfigPreDeployAttribute($value){
        $this->attributes['config_pre_deploy'] =trim(implode("|",$value),',');
    }
    public function getConfigPreDeployAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setConfigOnDeployAttribute($value){
        $this->attributes['config_on_deploy'] =trim(implode("|",$value),',');
    }
    public function getConfigOnDeployAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setConfigOnReleaseAttribute($value){
        $this->attributes['config_on_release'] =trim(implode("|",$value),',');
    }
    public function getConfigOnReleaseAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setConfigPostReleaseAttribute($value){
        $this->attributes['config_post_release'] =trim(implode("|",$value),',');
    }

    public function getConfigPostReleaseAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setConfigPostDeployAttribute($value){
        $this->attributes['config_post_deploy'] =trim(implode("|",$value),',');
    }
    public function getConfigPostDeployAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setCustomPreDeployAttribute($value){
        $this->attributes['custom_pre_deploy'] =trim(implode("|",$value),',');
    }
    public function getCustomPreDeployAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setCustomOnDeployAttribute($value){
        $this->attributes['custom_on_deploy'] =trim(implode("|",$value),',');
    }
    public function getCustomOnDeployAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setCustomOnReleaseAttribute($value){
        $this->attributes['custom_on_release'] =trim(implode("|",$value),',');
    }
    public function getCustomOnReleaseAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setCustomPostReleaseAttribute($value){
        $this->attributes['custom_post_release'] =trim(implode("|",$value),',');
    }

    public function getCustomPostReleaseAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setCustomPostDeployAttribute($value){
        $this->attributes['custom_post_deploy'] =trim(implode("|",$value),',');
    }

    public function getCustomPostDeployAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setConfigHostsAttribute($value){
        $this->attributes['config_hosts'] =trim(implode("|",$value),',');
    }

    public function getConfigHostsAttribute($value){
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function setConfigExludeAttribute($value){
        if (!empty($value)) {
            $this->attributes['config_exlude'] =trim(implode("|",$value),',');
        }
    }

    public function getConfigExludeAttribute($value)
    {
        $arr = explode('|', $value);
        if (count($arr) > 1) {
            $str = implode("\r\n", $arr);
            return $str;
        } else {
            return $value;
        }
    }

    public function deployTask()
    {
        return $this->belongsTo(DeploymentTask::class);
    }

    public static function getConfigInfo()
    {
        $options = self::select('id', 'config_name as text')->get();
        $selection = [];
        foreach ($options as $k => $v) {
            $selection[$v->id]    = $v->text;
        }

        return $selection;
    }

    public static function getConfigData($id)
    {
        return self::find($id, ['id', 'config_name', 'config_env', 'config_branch']);
    }


    public static function getRemoteDir($id)
    {
        $arr = self::find($id, ['config_hosts']);
    }
}
