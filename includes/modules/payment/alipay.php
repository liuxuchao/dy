<?php

/**
 * ECSHOP 支付宝插件
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: douqinghua $
 * $Id: alipay.php 17217 2011-01-19 06:29:08Z douqinghua $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

use Payment\Common\PayException;
use Payment\Client\Charge;
use Payment\Client\Notify;
use Payment\Client\Query;
use Payment\Config;
use Payment\Notify\PayNotifyInterface;

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/alipay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'alipay_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'ECMOBAN TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.alipay.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.2';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'alipay_account',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_key',               'type' => 'text',   'value' => ''),
        array('name' => 'alipay_partner',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_pay_method',        'type' => 'select', 'value' => ''),
        array('name' => 'use_sandbox',              'type' => 'select', 'value' => ''),
        array('name' => 'app_id',                   'type' => 'text',   'value' => ''),
        array('name' => 'sign_type',                'type' => 'select', 'value' => ''),
        array('name' => 'ali_public_key',           'type' => 'textarea', 'value' => ''),
        array('name' => 'rsa_private_key',          'type' => 'textarea', 'value' => ''),
    );

    return;
}

/**
 * 类
 */
class alipay
{
	/**
     * 生成支付代码
     * @param $order 订单信息
     * @param $payment 支付方式
     * @return string
     */
    public function get_code($order, $payment)
    {
        // 订单信息
        $payData = [
            'body' => $order['order_sn'],
            'subject' => $order['order_sn'],
            'order_no' => $this->make_trade_no($order['log_id'], $order['order_amount']), // $order['order_sn'] . 'O' . $order['log_id'],
            'timeout_express' => time() + 3600 * 24,// 表示必须 24h 内付款
            'amount' => $order['order_amount'],// 单位为元 ,最小为0.01
            'return_param' => (string) $order['log_id'],// 一定不要传入汉字，只能是 字母 数字组合
            'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',// 客户地址
            'goods_type' => 1,
            'store_id' => '',
        ];

        try {
            $payUrl = Charge::run(Config::ALI_CHANNEL_WEB, $this->getConfig(), $payData);
        } catch (PayException $e) {
            // 异常处理
            exit($e->getMessage());
        }

        if(isset($order['merge']) && $order['merge'] == 1)
        {
            return $payUrl;
        }
        else
        {
            return '<div class="alipay" style="text-align:center"><input type="button" onclick="window.open(\'' . $payUrl . '\')" value="' .$GLOBALS['_LANG']['pay_button']. '" /></div>';
        }
        /* 生成支付按钮 */
        //return '<div class="alipay" style="text-align:center"><input type="button" onclick="window.open(\'' . $payUrl . '\')" value="' .$GLOBALS['_LANG']['pay_button']. '" /></div>';
    }

    /**
     * 同步通知
     * @return mixed
     */
    public function respond()
    {
        if (!empty($_GET)) {
            try {
                $log_id = $this->parse_trade_no($_GET['out_trade_no']);
                $sql = 'SELECT oi.order_sn, pl.log_id, pl.order_amount from ' . $GLOBALS['ecs']->table('pay_log')
                    . ' as pl LEFT JOIN ' . $GLOBALS['ecs']->table('order_info')
                    . ' as oi ON pl.order_id = oi.order_id WHERE pl.log_id = \'' . $log_id . '\'';
                $order = $GLOBALS['db']->getRow($sql);
                return $this->query($order);
            } catch (PayException $e) {
                $this->logResult($e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 异步通知
     * @return mixed
     */
    public function notify()
    {
        unset($_POST['code']);
        
        $config = $this->getConfig();
        $config['notify_url'] = str_replace('api/notify/api/notify', 'api/notify', $config['notify_url']);
        $config['return_url'] = str_replace('/api/notify', '', $config['return_url']);
        
        $_POST['fund_bill_list'] = stripslashes($_POST['fund_bill_list']);
		
        if (!empty($_POST)) {
            try {
                $callback = new OrderPaidNotify();
                $ret = Notify::run(Config::ALI_CHARGE, $config, $callback);// 处理回调，内部进行了签名检查
                exit($ret);
            } catch (PayException $e) {
                $this->logResult($e->getMessage());
                exit('fail');
            }
        } else {
            exit("fail");
        }
    }

    /**
     * 订单查询
     * @return mixed
     */
    public function query($order)
    {
        $data = [
            'out_trade_no' => $this->make_trade_no($order['log_id'], $order['order_amount']), // $order['order_sn'] . 'O' . $order['log_id'],
        ];
		
        try {
            $ret = Query::run(Config::ALI_CHARGE, $this->getConfig(), $data);
            if ($ret['response']['trade_state'] === Config::TRADE_STATUS_SUCC) {
                order_paid($order['log_id'], 2, '', $order['order_sn']);
                return true;
            }
        } catch (PayException $e) {
            $this->logResult($e->getMessage());
        }

        return false;
    }

    /**
     * 获取配置
     * @return array
     */
    private function getConfig()
    {
		if(!function_exists('get_payment')){
			include_once(ROOT_PATH  . '/includes/lib_payment.php');
		}
        $payment = get_payment(basename(__FILE__, '.php'));

        return [
            'use_sandbox' => (bool)$payment['use_sandbox'],
            'partner' => $payment['alipay_partner'],
            'app_id' => $payment['app_id'],
            'sign_type' => $payment['sign_type'],
            // 可以填写文件路径，或者密钥字符串  当前字符串是 rsa2 的支付宝公钥(开放平台获取)
            'ali_public_key' => $payment['ali_public_key'],
            // 可以填写文件路径，或者密钥字符串  我的沙箱模式，rsa与rsa2的私钥相同，为了方便测试
            'rsa_private_key' => $payment['rsa_private_key'],
            'notify_url' => notify_url(basename(__FILE__, '.php')),
            'return_url' => return_url(basename(__FILE__, '.php')),
            'return_raw' => false,
        ];
    }
	
    //打印日志
    private function logResult($word = '') {
        $word = is_array($word) ? var_export($word, 1) : $word;
        $fp = fopen(ROOT_PATH . "/data/alipaylog.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    
    /**
     * 生成支付订单号
     * @param $log_id
     * @param $order_amount
     * @return string
     */
    private function make_trade_no($log_id, $order_amount)
    {
        $trade_no = '6';
        $trade_no .= str_pad($log_id, 15, 0, STR_PAD_LEFT);
        $trade_no .= str_pad($order_amount * 100, 16, 0, STR_PAD_LEFT);

        return $trade_no;
    }

    /**
     * @param $trade_no
     * @return int
     */
    private function parse_trade_no($trade_no)
    {
        $log_id = substr($trade_no, 1, 15);

        return intval($log_id);
    }
}

/**
 * 客户端需要继承该接口，并实现这个方法，在其中实现对应的业务逻辑
 * Class OrderPaidNotify
 */
class OrderPaidNotify implements PayNotifyInterface
{
    public function notifyProcess(array $data)
    {
        /**
         * 改变订单状态
         */
        $log_id = $data['return_param']; // 订单号log_id
        order_paid($log_id, 2, '', $data['subject']);
        return true;
    }
}
