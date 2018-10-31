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


    public function setConfigPreDeployAttribute($value){
        $this->attributes['config_pre_deploy'] =trim(implode(",",$value),',');
    }

    public function setConfigOnDeployAttribute($value){
        $this->attributes['config_on_deploy'] =trim(implode(",",$value),',');
    }

    public function setConfigOnReleaseAttribute($value){
        $this->attributes['config_on_release'] =trim(implode(",",$value),',');
    }
    public function setConfigPostReleaseAttribute($value){
        $this->attributes['config_post_release'] =trim(implode(",",$value),',');
    }

    public function setConfigPostDeployAttribute($value){
        $this->attributes['config_post_deploy'] =trim(implode(",",$value),',');
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
}
