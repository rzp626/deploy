<?php

namespace App\Http\Controllers;

use App\Services\UtilsService;
use http\Env\Response;
use Illuminate\Http\Request;
use Log;
use DB;

class ConfigController extends Controller
{
    /**
     * 验证该路径是否存在
     *
     * @param Request $request
     * @return bool
     */
    public function validDir(Request $request)
    {
        $data = [
            'code' => 4000,
            'msg' => 'Wrong dir params, please check it.',
        ];
        $dir = $request->get('dir');
        Log::info('Enter the dir is '.$dir);

        if (!isset($dir) || empty($dir))
            return response()->json($data);
        Log::info('php '.substr($dir, -strlen('.php')));

        if ((substr($dir, -strlen('.php')) != '.php')
            && (substr($dir, -strlen('.ini')) != '.ini')
            && (substr($dir, -strlen('.yml')) != '.yml')
            && (substr($dir, -strlen('.conf')) != '.conf')  ) {
            $data['code'] = 4004;
            $data['msg'] = '请输入有效路径';
            return response()->json($data);
        }

        Log::info('dirname '.dirname($dir));
        if (strpos($dir, DIRECTORY_SEPARATOR) !== false) {
            if (!file_exists(dirname($dir))) {
                $data['code'] = 4004;
                $data['msg'] = '请输入有效路径';
                return response()->json($data);
            }
        } else {
            $fileDir = config_path(). '/' . $dir;
            Log::info('the config dir '.$fileDir);
            if (file_exists($fileDir)) {
                $data['code'] = 2001;
                $data['msg'] = '该配置文件已存在，确定要覆盖此文件内容吗？';
                return response()->json($data);
            }
        }

        $data['code'] = 2000;
        $data['msg'] = '配置文件验证通过';
        return response()->json($data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFile(Request $request)
    {
        $data = [
            'code' => 4000,
            'msg' => 'Wrong params, check it.',
        ];

        $dir = $request->get('dir');
        $content = $request->get('content');
        if (!isset($dir)
            || empty($dir)
            || !isset($content)
            || empty($content)) {
            return response()->json($data);
        }


        Log::info('dirname '.dirname($dir));
        if (strpos($dir, DIRECTORY_SEPARATOR) === false) {
            $dir = config_path(). '/' . $dir;
        }

        $ret = UtilsService::saveConfig($dir, $content);
        if ($ret === false) {
            Log::info('create or update config file is failed, check it!');
            $data['code']= 5000;
            $data['msg'] = '创建配置文件失败';
            return response()->json($data);
        }

        $data['code']= 2000;
        $data['msg'] = '成功创建配置文件';
        return response()->json($data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createGroupUser(Request $request)
    {
        $data = [
            'code' => 4000,
            'msg'  => 'Wrong params, check it.',
        ];

        $groupId = $request->get('groupId');
        $userId = $request->get('userId');
        if (!isset($groupId) || empty($groupId) || !isset($userId) || empty($userId)) {
            return response()->json($data);
        }

        if (!is_numeric((int)$groupId) || !is_numeric((int)$userId)) {
            Log::info('the group id: '.$groupId.' and the user id: '.$userId);
            return response()->json($data);
        }

        $res = $this->isGroupUser($userId, $groupId);
        if ($res) {
            $data['code'] = 5000;
            $data['msg'] = '该组已有此用户！';
            return $data;
        }

        // 添加用户
        DB::table('admin_group_users')->insert(['user_id' => $userId, 'group_id' => $groupId, 'created_at' => date('Y-m-d H:i:s', time())]);

        $data = [
            'code' => 2000,
            'msg' => '成功分配组用户',
        ];
        return response()->json($data);
    }

    /**
     * @param Request $request
     */
    public function isGroupUser($userId, $groupId)
    {
        $ret = DB::table('admin_group_users')->where('user_id', $userId)->where('group_id', $groupId)->get();
        $cnt = $ret->count();
        if ($cnt == 0) {
            return false;
        }
        Log::info('query the same group_user, the result is '. json_encode($ret));
        return true;
    }
}


