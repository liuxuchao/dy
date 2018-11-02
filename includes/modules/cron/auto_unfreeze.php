<?php

/**
 * ECMOBAN 程序说明 自动冻结解冻
 * ===========================================================
 * * 版权所有 2005-2018 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: ECMOBAN TEAM $
 * $Id: auto_unfreeze.php 2018-06-14 ECMOBAN TEAM $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/auto_unfreeze.php';
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
    $modules[$i]['desc']    = 'auto_unfreeze_desc';

    /* 作者 */
    $modules[$i]['author']  = 'ECMOBAN TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecmoban.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'auto_unfreeze_count', 'type' => 'select', 'value' => '5'),
    );

    return;
}

$debug = true; // true 开启日志 false 关闭日志
$log = false;//有改变账单的时候再记录日志

$time = gmtime();
$limit = !empty($cron['auto_unfreeze_count']) ? $cron['auto_unfreeze_count'] : 10;//自动操作数量

// 查询 seller_commission_bill表 冻结解冻天数 > 0 并且冻结时间存在

// 冻结解冻天数：frozen_data > 0
// 冻结时间：frozen_time > 0

$no_main_order  = "";
$where          = " WHERE 1 AND frozen_data > 0 AND frozen_time > 0 ";
$orderBy        = " ORDER BY frozen_time ASC, frozen_data ASC ";
$offset = " LIMIT 0, $limit ";

$sql = " SELECT id AS bill_id, seller_id, should_amount, chargeoff_status, frozen_money, frozen_data, frozen_time FROM " . $GLOBALS['ecs']->table('seller_commission_bill') . $where . $orderBy . $offset;
$bill_list = $GLOBALS['db']->getAll($sql);

if (!empty($bill_list)) {
    foreach ($bill_list as $key => $value) {
        $final_unfreeze_time = $value['frozen_time'] +  24 * 3600 * $value['frozen_data'];//最终解冻时间
        // if ($time >= $final_unfreeze_time && $value['chargeoff_status'] != 2) {
            // 账单自动解冻
            $detail = [];
            $detail['chargeoff_status'] = 2;
            if(!$value['chargeoff_time']){
                $detail['chargeoff_time'] = $time;
            }
            $detail['frozen_money'] = 0;
            $detail['settleaccounts_time'] = $time;
            $detail['should_amount'] = $value['should_amount'] + $value['frozen_money'];

            //更新商家余额
            $sql = "UPDATE " . $ecs->table('seller_shopinfo') . " SET seller_money = seller_money + '" . $value['frozen_money'] . "' WHERE ru_id = '" . $value['seller_id'] . "'";
            $db->query($sql);

            //更新结账单
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('seller_commission_bill'), $detail, 'UPDATE', "id = '".$value['bill_id']."'");

            $change_desc = sprintf($_LANG['seller_bill_unfreeze'], $_SESSION['admin_name']);
            $user_account_log = array(
                'user_id' => $value['seller_id'],
                'user_money' => $frozen_money,
                'change_time' => $time,
                'change_desc' => $change_desc,
                'change_type' => 2
            );
            // 操作日志
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('merchants_account_log'), $user_account_log, 'INSERT');
            $log = true;
        // }
    }
}


if ($debug == true && $log == true) {
    auto_unfreeze_logResult('==================== cron log ====================');
    auto_unfreeze_logResult($bill_list);
}

/**
 * 写入日志文件
 *
 * @param string $word
 * @param string $type
 */
function auto_unfreeze_logResult($word = '', $type = 'auto_unfreeze')
{
    $word = is_array($word) ? var_export($word, true) : $word;
    $suffix = '_' . substr(md5(__DIR__), 0, 6);
    $fp = fopen(ROOT_PATH . 'temp/' . $type . $suffix . '.log', "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . date("Y-m-d H:i:s", time()) . "\n" . $word . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}