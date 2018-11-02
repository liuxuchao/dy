<?php

namespace App\Modules\Api\Controllers\V3;

use App\Modules\Api\Controllers\Controller;

class UserController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With, Origin, Accept");
    }

    /**
     * Sign In 登录
     */
    public function actionSignIn()
    {
        $payload = file_get_contents('php://input');
        $payload = json_decode($payload, true);
        $user = [
            'username' => $payload['username'],
            'password' => $payload['password'],
        ];

        if ($user['username'] == 'demo' && $user['password'] == 'demo123') {
            $user['uid'] = 100;

            // 用户 token
            $token = $this->encode($user);

            $this->result(['token' => $token]);
        } else {
            $this->result($user, 1, '用户名或密码错误');
        }
    }

    /**
     * Sign In 注册
     */
    public function actionSignUp()
    {
        $username = I('username');
        $password = I('password');

        // 解析
        $res = []; // $this->decode($token);

        $this->result($res);
    }

    /**
     * 用户信息
     */
    public function actionInfo()
    {
        $payload = file_get_contents('php://input');
        $payload = json_decode($payload, true);
        $token = $payload['token'];

        // 解析
        $res = $this->decode($token);

        if ($res === false) {
            $this->result($token, 1, 'Token验证数据异常');
        } else {
            $this->result($res);
        }
    }

}