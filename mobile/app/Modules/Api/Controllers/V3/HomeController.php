<?php

namespace App\Modules\Api\Controllers\V3;

use App\Modules\Api\Controllers\Controller;

class HomeController extends Controller
{
    public function actionIndex()
    {
        $user = [
            'uid' => 100,
            'username' => 'test123',
        ];

        // 用户 token
        $token = $this->encode($user);

        // 解析
        $res = $this->decode('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiIiLCJhdWQiOiIiLCJpYXQiOjE1MjU5NDI3MTQsIm5iZiI6MTUyNTk0MjcxNCwiZXhwIjoxNTI4NTM0NzE0LCJwYXlsb2FkIjp7InVpZCI6MTAwLCJ1c2VybmFtZSI6InRlc3QxMjMifX0.RRB7ADWd0x7JmF1WguvcFcCOaPUs-8h7op1AgrMcUWc');

        $this->result(['token' => $token, 'data' => $res]);
    }

}