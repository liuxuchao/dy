<?php

namespace App\Modules\Api\Controllers;

use Firebase\JWT\JWT;
use Think\Controller\RestController;

class Controller extends RestController
{
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = require CONF_PATH . 'jwt.php';
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed $data 要返回的数据
     * @param  integer $code 返回的code
     * @param  mixed $msg 提示信息
     * @param  string $type 返回数据格式
     * @return string
     */
    protected function result($data, $code = 0, $msg = 'success', $type = 'json')
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => date('Y-m-d H:i:s'),
            'data' => $data,
        ];

        $this->response($result, $type);
    }

    /**
     * Token encode
     * @param array $payload
     * @return string
     */
    protected function encode($payload = [])
    {
        $time = time();

        $token = [
            'iss' => $this->config['iss'], // 签发者
            'aud' => $this->config['aud'], // jwt所面向的用户
            'iat' => $time, // 签发时间
            'nbf' => $time, // 在什么时间之后该jwt才可用
            'exp' => $time + $this->config['exp'], // 过期时间
            'payload' => $payload,
        ];

        return JWT::encode($token, $this->config['secret']);
    }

    /**
     * Token decode
     * @param string $jwt
     * @return array|boolean
     */
    protected function decode($jwt = '')
    {
        try {
            $token = (array)JWT::decode($jwt, $this->config['secret'], [$this->config['alg']]);
        } catch (\Exception $e) {
            return false;
        }

        return $token['payload'];
    }

}