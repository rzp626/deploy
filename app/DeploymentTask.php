<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\DeploymentConfig;

class DeploymentTask extends Model
{
    //
    public function deploy_config()
    {
        return $this->hasOne(DeploymentConfig::class);
    }

    public static function getRelationInfo()
    {
        $options = self::select('id', 'task_name as text')->orderBy('id', 'desc')->get();
        $selection = [];
        foreach ($options as $k => $v) {
            $selection[$v->id]    = $v->text;
        }

        return $selection;
    }

}
