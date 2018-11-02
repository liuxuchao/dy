<?php

namespace App\Channels\Sms\Driver;

use App\Extensions\Http;

class Dscsms
{

    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'app_key' => '',
        'app_secret' => '',
    ];

    /**
     * @var objcet 短信对象
     */
    protected $sms_api = "https://cloud.ecjia.com/sites/api/?url=sms/send";
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
                'app_key' => $this->config['app_key'],
                'app_secret' => $this->config['app_secret'],
                'mobile' => $mobile,
                'content' => $this->content
            ];
            $res = Http::doPost($this->sms_api, $post_data);
            $data = json_decode($res, true);
            //print_r($data);exit; //开启调试模式 TODO 此处暂时只能发送一次
            if ($data['status']['succeed']) {
                return true;
            } else {
                $this->errorInfo = $data['status']['error_desc'];
                logResult($this->errorInfo, 'sms');
                return false;
            }
        }
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
