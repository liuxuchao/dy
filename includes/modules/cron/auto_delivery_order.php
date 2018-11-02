<?php

/**
 * ECMOBAN 程序说明 自动确认收货
 * ===========================================================
 * * 版权所有 2005-2018 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: ECMOBAN TEAM $
 * $Id: auto_delivery_order.php 2018-06-14 ECMOBAN TEAM $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/auto_delivery_order.php';
if (file_exists($cron_lang))
{
    global $_LANG;

    include_once($cron_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'auto_delivery_order_desc';

    /* 作者 */
    $modules[$i]['author']  = 'ECMOBAN TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecmoban.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'auto_delivery_order_count', 'type' => 'select', 'value' => '5'),
    );

    return;
}

$debug = true; // true 开启日志 false 关闭日志

$time = gmtime();
$limit = !empty($cron['auto_delivery_order_count']) ? $cron['auto_delivery_order_count'] : 10;//自动操作数量

// 是否开启自动确认收货 0 关闭,1 开启
$open_delivery_time = isset($_CFG['open_delivery_time']) ? $_CFG['open_delivery_time'] : 0;

if ($open_delivery_time == 1) {
    // 查询 已付款、已发货的订单

    // 订单状态：已确认 OS_CONFIRMED 1 、已分单 OS_SPLITED 5、退货 OS_RETURNED 4
    // 支付状态：已付款 PS_PAYED 2
    // 配送状态：已发货 SS_SHIPPED 1

    $no_main_order  = "";
    $where          = " WHERE 1 AND o.order_status in (1, 4, 5) AND o.pay_status in (2) AND o.shipping_status in (1) ";
    $orderBy        = " ORDER BY o.order_id ";
    $sort           = " ASC ";
    $offset = " LIMIT 0, $limit ";

    //主订单下有子订单时，则主订单不显示
    $no_main_order = " AND (SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_info') . " AS oi2 WHERE oi2.main_order_id = o.order_id) = 0 ";

    $sql = " SELECT o.order_id, o.order_sn, o.order_status, o.shipping_time, o.auto_delivery_time FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " . $where . $no_main_order . $orderBy . $sort . $offset;
    $order_list = $GLOBALS['db']->getAll($sql);

    if (!empty($order_list)) {
        foreach ($order_list as $key => $value) {

            $delivery_time = $value['shipping_time'] + 24 * 3600 * $value['auto_delivery_time']; // 订单应收货时间
            if ($time >= $delivery_time) {
                // 自动确认发货操作
                $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET order_status = '" . $value['order_status'] . "', shipping_status = '" .SS_RECEIVED. "', pay_status = '" .PS_PAYED. "' WHERE order_id = ".$value['order_id'];
                $GLOBALS['db']->query($sql);
                // 操作日志
                order_action($value['order_sn'], $value['order_status'], SS_RECEIVED, PS_PAYED, $GLOBALS['_LANG']['self_motion_goods'], $GLOBALS['_LANG']['auto_system'], 0, $time);
            }
        }
    }
}


if ($debug == true) {
    auto_delivery_order_logResult('==================== cron log ====================');
    auto_delivery_order_logResult($order_list);
}

/**
 * 写入日志文件
 *
 * @param string $word
 * @param string $type
 */
function auto_delivery_order_logResult($word = '', $type = 'auto')
{
    $word = is_array($word) ? var_export($word, true) : $word;
    $suffix = '_' . substr(md5(__DIR__), 0, 6);
    $fp = fopen(ROOT_PATH . 'temp/' . $type . $suffix . '.log', "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . date("Y-m-d H:i:s", time()) . "\n" . $word . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}