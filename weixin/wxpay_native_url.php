<?php

/**
 * 微信支付异步响应操作(微信公众平台地址)
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/../includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

/* 支付方式代码 */
$pay_code = 'wxpay';
/* 支付信息 */
$payment  = get_payment($pay_code);

// 获取异步数据
$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

if(!empty($postStr)){
    $postData = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    $postData['AppKey'] = $payment['wxpay_paysignkey'];
    foreach($postData as $k=>$v){
        $_GET[$k] = $v;
    }

    $plugin_file = ROOT_PATH . 'includes/modules/payment/' . $pay_code . '.php';
    /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
    if (file_exists($plugin_file)) {
        /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
        include_once($plugin_file);
        require(ROOT_PATH . 'includes/lib_clips.php');
        require(ROOT_PATH . 'includes/lib_transaction.php');
        //订单信息
		$order_id = $GLOBALS['db']->getOne('SELECT order_id FROM '.$GLOBALS['ecs']->table('order_info').' WHERE order_sn = '.$_GET['ProductId']);
        $order = get_order_detail($order_id);

        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);

        $string = '';
        foreach($_GET as $k=>$v){
            if(null != $v && 'null' != $v && 'AppSignature' != $k && 'SignMethod' != $k){
                $string .= strtolower($k) .'='. $v .'&';
            }
        }
        $string = substr($string, 0, -1);
        //默认签名
        $sign = $_GET['AppSignature'];
        //生成签名
        $content = SHA1($string);

        $wxpayObj = new $pay_code();
        //验证签名
        if($sign == $content){
            //生成原生支付请求xml
            echo $wxpayObj->getpackage($order, $payment, 0, "ok");
        }
        else{
             echo $wxpayObj->getpackage($order, $payment, 1, "签名验证失败");
             exit; 
        }
        exit;
    } 
    else {
        echo $wxpayObj->getpackage($order, $payment, 1, "插件文件不存在");
        exit;
    }
}
else{
    echo 'fail';
    exit;
}

//打印日志
function logResult($word='') {
    $fp = fopen("log.txt","a");
    flock($fp, LOCK_EX) ;
    fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}
?>