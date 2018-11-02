<?php

namespace App\Channels\Sms\Driver;

use App\Extensions\Http;

class Ihuyi
{

    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'sms_name' => '',
        'sms_password' => '',
    ];

    /**
     * @var objcet 短信对象
     */
    protected $sms_api = "https://106.ihuyi.com/webservice/sms.php?method=Submit";
    protected $content = null;
    protected $phones = [];
    protected $errorInfo = null;

    /**
     * 构建函数
     * @param array $config 短信配置
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 设置短信信息
     * @access public
     * @param string $body 邮件内容
     * @return boolean
     */
    public function setSms($title, $content)
    {
        $sql = "SELECT * FROM {pre}alidayu_configure WHERE send_time = '$title'";
        $msg = $GLOBALS['db']->getRow($sql);
        // 替换消息变量
        preg_match_all('/\$\{(.*?)\}/', $msg['temp_content'], $matches);
        foreach ($matches[1] as $vo) {
            $msg['temp_content'] = str_replace('${' . $vo . '}', $content[$vo], $msg['temp_content']);
        }
        $this->content = $msg['temp_content'];
        return $this;
    }

    /**
     * 发送短信
     * @param  string $to 收件人
     * @return boolean
     */
    public function sendSms($to)
    {
        $sendTo = explode(",", $to);
        foreach ($sendTo as $add) {
            if (is_mobile($add)) {
                $this->addPhone($add);
            }
        }
        if (!$this->send()) {
            $return = false;
        } else {
            $return = true;
        }
        return $return;
    }

    private function addPhone($add)
    {
        array_push($this->phones, $add);
    }

    private function send()
    {
        foreach ($this->phones as $mobile) {
            $post_data = [
                'account' => $this->config['sms_name'],
                'password' => $this->config['sms_password'],
                'mobile' => $mobile,
                'content' => $this->content
            ];
            $res = Http::doPost($this->sms_api, $post_data);
            $data = $this->xmlToArray($res);
            //print_r($data);exit; //开启调试模式 TODO 此处暂时只能发送一次
            if ($data['SubmitResult']['code'] == 2) {
                return true;
            } else {
                $this->errorInfo = $data['SubmitResult']['msg'];
                logResult($this->errorInfo, 'sms');
                return false;
            }
        }
    }

    private function xmlToArray($xml)
    {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = $this->xmlToArray($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    /**
     * 返回错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorInfo;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        unset($this->sms);
    }
}
