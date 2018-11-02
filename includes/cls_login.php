<?php

/**
 * ECSHOP 基础类
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: cls_ecshop.php 17217 2011-01-19 06:29:08Z liubo $
 */
if (!defined('IN_ECS')) {
    die('Hacking attempt');
}

class ecs_login {

    var $access_token = '';
    var $get_openid_url = "https://graph.qq.com/oauth2.0/me?";

    /**
     * 构造函数
     *
     * @access  public
     * @param   string      $ver        版本号
     *
     * @return  void
     */
    function __construct($access_token) {
        $this->access_token = $access_token;
    }

    /**
     * 获取登录用户的unionid
     *
     * @return json  callback({"client_id":"YOUR_APPID", "openid":"YOUR_OPENID", "unionid":"YOUR_UNIONID"})
     */
    public function get_unionid() {
        $params = array(
            'access_token' => $this->access_token,
            'unionid' => 1
        );
        $url = $this->get_openid_url . http_build_query($params, '', '&');
        $result_str = $this->http($url);
        $json_r = array();
        if ($result_str) {

            preg_match('/callback\(\s+(.*?)\s+\)/i', $result_str, $result_a);
            $json_r = json_decode($result_a[1], true);

            if (!$json_r || !empty($json_r['error'])) {
                $errCode = $json_r['error'];
                $errMsg = $json_r['error_description'];
                return false;
            }

            return $json_r['unionid'];
        }
        return false;
    }

    /**
     * 提交请求
     *
     * @param unknown $url
     * @param string  $postfields
     * @param string  $method
     * @param unknown $headers
     * @return mixed
     */
    private function http($url, $postfields = '', $method = 'GET', $headers = array()) {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        if ($method == 'POST') {
            curl_setopt($ci, CURLOPT_POST, TRUE);
            if ($postfields != '')
                curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        }
        $headers[] = 'User-Agent: ECTouch.cn';
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

}

?>