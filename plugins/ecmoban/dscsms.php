<?php

/**
 * DSC 大商创通信类
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: dscsms.php 17217 2011-01-19 06:29:08Z liubo $
 */
if (!defined('IN_ECS')) {
    die('Hacking attempt');
}

class dscsms {

    private $app_key = ""; //密钥
    private $app_secret = ""; //密钥
    private $action = "send";
    private $protocolType = "https";
    private $domain = "cloud.ecjia.com";
    private $graphUrl = "/sites/api/?url=sms/send";
    private $getMethod = "GET";

    /**
     * 构造函数
     *
     * @access  public
     * @param   string
     *
     * @return  void
     */
    function __construct($app_key = '', $app_secret = '') {
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
    }
    
    /**
     * 获取url
     */
    public function getUrl() {
        return $this->protocolType . "://" . $this->domain . $this->graphUrl;
    }
    
    /**
     * 组合数据
     */
    public function composeData($apiParams = array()) {
        if(!empty($apiParams['TemplateContent']) && !empty($apiParams['TemplateParam'])){
            $TemplateParam = json_decode($apiParams['TemplateParam'], true);
            preg_match_all("/\{(.+?)\}/", $apiParams['TemplateContent'], $match);
            foreach($match[1] as $key=>$val){
                $apiParams['TemplateContent'] = str_replace('${'.$val.'}', $TemplateParam[$val], $apiParams['TemplateContent']);
            }
        }
        
        $sendParams = array(
            'app_key' => $this->app_key,
            'app_secret' => $this->app_secret,
            'mobile' => $apiParams['PhoneNumbers'],
            'content' => $apiParams['TemplateContent'],
        );
        
        return $sendParams;
    }
    
    /**
     * 组合url
     */
    public function composeUrl($apiParams = array()) {
        $sendParams = composeData($apiParams);
        
        $requestUrl = $this->getUrl();
        
        foreach ($sendParams as $apiParamKey => $apiParamValue) {
            $requestUrl .= "&" . "$apiParamKey=" . urlencode($apiParamValue);
        }
        return substr($requestUrl, 0, -1);
    }
    
    /**
     * 发送操作
     */
    public function send($url, $data) {
        
        $http = new Http();
        if(isset($data) && is_array($data)){
            $resp = $http->doPost($url, $data);
        }else{
            $resp = $http->doGet($url);
        }
        $resp = json_decode($resp, true);
        
        if ($resp['status']['succeed'] == 1) {
            return true;
        } else {
            $errorInfo = $resp['status']['error_desc'];
            //$this->logResult($this->errorInfo, 'sms');
            
            return $errorInfo;
        }
    }
    
    /**
     * 写入日志文件
     * @param string $word
     */
    private function logResult($word = '') {
        $word = is_array($word) ? var_export($word, true) : $word;
        $fp = fopen(ROOT_PATH . 'sms/dscsms_log.txt', "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . date("Y-m-d H:i:s", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

}

?>