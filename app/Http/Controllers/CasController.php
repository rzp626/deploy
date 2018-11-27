<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Log;
use Symfony\Component\HttpFoundation\Request;
use Encore\Admin\Controllers\AuthController;

class CasController extends AuthController
{
    /**
     * @param void
     * @return url
     */
    public function login()
    {
        $cas = "http://cas.erp.sina.com.cn/cas";
        $service =  url( '/' );
        $vurl = $cas . "/login?service=" . urlencode ( $service ) . "&ext=";
        $ticket = isset ( $_REQUEST ["ticket"] ) ? $_REQUEST ["ticket"] : "";
        if ($ticket == "") {
            return redirect($vurl);
        } else {
            $vurl = $cas . "/validate?ticket=" . $ticket . "&service=" . urlencode ( $service );
            $validate = file_get_contents( $vurl );
            $xml = simplexml_load_string($validate);
            $json = json_encode($xml);
            $arr = json_decode($json, true);

            if (!isset($arr) || empty($arr) || !array_key_exists('info', $arr) || empty($arr['info'])) {
                header('Cache-control: private, must-revalidate');
                echo "<script>alert('该用户存在，请检查');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
                exit;
            }

            $cnt = count($arr['info']);
            if ($cnt < 5) {
                header('Cache-control: private, must-revalidate');
                echo "<script>alert('该用户存在，请检查');location.href='".$cas."';</script>";
                exit;
            } else {
                Log::info('the operator info is '.print_r($arr, true));
                $username = $arr['info']['email'];
                $password = $arr['info']['username'];
                if (empty($username) || empty($password)) {
                    header('Cache-control: private, must-revalidate');
                    $url = url('admin');
                    echo "<script>alert('cas登陆失败，请使用账号密码登陆');location.href='".$url."';</script>";
                    exit;
                }

                // 成功跳转前，增加登录次数
                if (Cache::has('loginNum')) {
                    Cache::increment('loginNum');
                } else {
                    $endTime = strtotime(date('Y-m-d', time()).' 23:59:59');
                    $lifeTime = round(($endTime - time()) / 60);
                    Cache::put('loginNum', 1, $lifeTime);
                }

                return redirect(url('test/?username='.$username.'&password='.$password));
            }
        }
    }

    /**
     * logout
     *
     * @param void
     * @return void
     */
    public function logout() {
        $cas = "http://cas.erp.sina.com.cn/cas";
        $service =  url( '/' );
        $vurl = $cas . "/logout?service=" . urlencode ( $service ) . "&ext=";

        $url = 'http://cas.erp.sina.com.cn/cas/logout';
        return redirect($url);
    }

    /**
     * 测试cas登陆
     *
     * @param Request $request
     * @return mixed
     */
    public function test(Request $request)
    {
        return $this->postLogin($request);
    }

}
