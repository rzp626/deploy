<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\DeploymentTask;
use Symfony\Component\Yaml\Yaml;

/**
 * 根据所加任务，修改magephp.yml配置项
 *
 * Class UtilsService
 * @package App\Services
 */
class UtilsService
{
    public static function changeConfigFunc($taskInfo)
    {
        if (!is_array($taskInfo) || !array_key_exists('task_branch', $taskInfo) || !array_key_exists('task_env', $taskInfo) || !array_key_exists('task_id', $taskInfo)) {
            Log::error(json_encode($taskInfo));
            return false;
        }

        $taskBranchArr = config('deployment.task_branch');
        $taskEnvArr = config('deployment.task_env');

        // 获取选择分支、发布环境
        $selTaskBranch = $taskBranchArr[$taskInfo['task_branch']];
        $selTaskEnv = $taskEnvArr[$taskInfo['task_env']];
        $selTaskId = $taskInfo['task_id'];

        return $selTaskId;
    }

    /**
     * 必填字段校验
     * @param $allFields
     * @param $filledFields
     * @param $initFields
     * @return bool
     */
    public static function filterFields(&$allFields, $filledFields, $initFields)
    {
        if (empty($allFields) || empty($filledFields)) {
            return false;
        }

        foreach ($allFields as $field => $value) {
            if (in_array($field, $filledFields) && (strlen($value) <= 0)) {
                return false;
            }

            if (in_array($field, $initFields)) {
                if (is_array($value)) {
                    $cnt = count($value);
                    if ($cnt == 1 && ($value[0] === null) ) {
                        $value = '';
                    }

                    if ($cnt > 1) {
                        foreach ($value as $k => $v) {
                            if ($v === null) {
                                unset($value[$k]);
                            }
                        }
                    }
                } else if (is_string($value)) {
                    if ($value === null) {
                        $value = '';
                    }
                }

            }
        }

        return true;
    }

    /**
     * 配置文件添加成功，执行的任务
     *
     * @param $configItem 配置项
     * @return bool
     */
    public static function generateConfigForMage($configItem)
    {
        if (!is_array($configItem) || empty($configItem)) {
            Log::info('the config params is wrong.check it.');
            return false;
        }
        extract($configItem);
        Log::info('Start to generate the mage.yml file.');
        $deployConfigArr = config('deployment');
        $tmplFields = $deployConfigArr['yml_map_fields'];
        $tmpl = $deployConfigArr['yml_template'];
        $deployConfig = $deployConfigArr['deploy_config'];
        $config_env = $deployConfig['task_env'][$config_env];
        $common_items = $deployConfigArr['common_mage_yml'];

        $mageYmlFile = [
            'magephp' => [
                'environments' => [],
            ]
        ];
        Log::info('tmpFields'.print_r($tmplFields,true));
        Log::info('tmp:'.print_r($tmpl, true));
        $split = '|';
        $customStr = 'custom_';
        $mageYmlFile['magephp']['environments'][$config_env] = $common_items;
        foreach ($tmpl as $key => $value) {
            Log::info('magephp'.print_r($mageYmlFile, true));
            if (isset($tmplFields[$key])) {
                $tmpArr = explode('|', $tmplFields[$key]);
                if ($tmpArr[1] == 'normal') {
                    $mageYmlFile['magephp']['environments'][$config_env][$key] = ${$tmpArr[0]};
                } else if ($tmpArr[1] == 'map') {
                    $mageYmlFile['magephp']['environments'][$config_env][$key] = $deployConfig['task_branch'][${$tmpArr[0]}];
                } else if ($tmpArr[1] == 'int') {
                    $mageYmlFile['magephp']['environments'][$config_env][$key] = (int)${$tmpArr[0]};
                } else if ($tmpArr[1] == 'array') {
                    if (is_string(${$tmpArr[0]})) {
                        if (${$tmpArr[0]} === null || ${$tmpArr[0]} == '') {
                            $mageYmlFile['magephp']['environments'][$config_env][$key] = '';
                        } else {
                            $mageYmlFile['magephp']['environments'][$config_env][$key] = explode($split, trim(${$tmpArr[0]}, "'"));
                        }
                    } else if (is_array(${$tmpArr[0]})) {
                        $customArr = $builInArr= [];
                        foreach (${$tmpArr[0]} as $k => $v) {
                            if (strpos($v, $customStr) !== false) {
                                $string = substr($v, strlen($customStr));
                                $tmpV = explode($split, $string);
                                $cnt = count($tmpV);
                                for ($i = 0; $i < $cnt; $i++) {
                                    $customArr[] = $tmpV[$i];
                                }
                            } else {
                                Log::info($deployConfig[$key]);
                                if (isset($deployConfig[$key])) {
                                    Log::info($deployConfig[$key]);
                                    $builInArr[] = $deployConfig[$key][$v];
                                }
                            }
                        }
                        $mageYmlFile['magephp']['environments'][$config_env][$key] = array_merge($builInArr, $customArr);
                    }
                }
            }
            Log::info('magephp'.print_r($mageYmlFile, true));
        }

        $yaml = preg_replace("/'/", '', Yaml::dump($mageYmlFile, 5, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        Log::info('the yaml content: '.print_r($yaml, true));
        file_put_contents(config_path() . '/mage/'.$config_env.'.mage.yml', $yaml);
    }
}
