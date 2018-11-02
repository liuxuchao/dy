<?php

namespace App\Repositories\Share;

use App\Models\Users as User;
use App\Models\Comment;

class ShareRepository
{
    protected $share;

    /**
     * 获取token
     * @param array $data
     * @return $token
     */
    public function token($app, $secret)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$app."&secret=".$secret;

        $token = file_get_contents($url);

        return $token;
    }


}
