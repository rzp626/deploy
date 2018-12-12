<?php

namespace App\Services;

use Log;

class GitRepoInfoService
{
    /**
     * @param $gitName
     * @return bool
     */
    public static function getGitInfo($gitName, $gitUser, $branch, $configId)
    {
        // clone或者pull对应的仓库
        if (!isset($gitName) || empty($gitName) || !is_string($gitName)
            || !isset($gitUser) || empty($gitUser) || !is_string($gitUser)
            || !isset($configId) || empty($configId)) {
            return false;
        }
        Log::info('git name: '.$gitName. ' and git user: '.$gitUser.' and the branch: '.$branch.' and the config Id: '.$configId);

        $repoPrefix = config('deployment.git_prefix');
        $fullGitPath = $repoPrefix . $gitName . '.git';
//        $fullGitPath = 'http://zhenpeng8:20181203%40rzp@git.intra.weibo.com/user_growth/msg-new.git';
        $srcPath = config('deployment.src_path');
        $srcGitPath = $srcPath.'/'.$gitName;

        if (is_numeric($branch)) {
            $configArr = config('deployment.deploy_config');
            $branchArr = $configArr['task_branch'];
            $branch = $branchArr[$branch];
        }

        try {
            if (!file_exists($srcGitPath)) {
                $action = 'clone';
//                $command = "nohup sudo -iu {$gitUser} sh -c 'cd {$srcPath} && git clone {$fullGitPath}' && sh switchBranch.sh {$gitName} {$action} {$branch} {$configId}";
                $command = "nohup sudo -iu {$gitUser} sh -c 'cd {$srcPath} ; sh switchBranch.sh {$gitName} {$action} {$branch} {$configId}'";
                $ret = exec("$command > /dev/null 2>&1 &", $output);
                Log::info('Clone: the cmd is '.$command . ' and the ret is '.json_encode($output) . ' & the ret: '.print_r($ret, true). ' the output: '.print_r($output, true));
            } else {
                $action = 'pull';
//                $command = "nohup sudo -iu {$gitUser} sh -c 'cd {$srcPath} && cd {$gitName} && sh switchBranch.sh {$gitName} {$action} {$branch} {$configId} && git pull $fullGitPath";
                $command = "nohup sudo -iu {$gitUser} sh -c 'cd {$srcPath} ; sh switchBranch.sh {$gitName} {$action} {$branch} {$configId}'";
                $ret = exec("$command > /dev/null 2>&1 &", $output);
                Log::info('Pull: the cmd is '.$command. ' and the exec ret: '.json_encode($output).' & the ret: '.print_r($ret, true). ' the output: '.print_r($output, true));
            }

            return true;
        } catch (\Exception $e) {
            Log::info('exception info is '.$e->getMessage());
            return 'fail';
        }
    }
}