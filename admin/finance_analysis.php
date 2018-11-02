<?php

/**
 * dsc 财务统计管理
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: guest_stats.php 17217 2018-04-02 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_statistical.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/statistic.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 结算
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'settlement_stats')
{
    $smarty->assign('total_stats', settlement_total_stats());

    $order_list = merchants_commission_list();
    
    $smarty->assign('full_page',    1);
    $smarty->assign('order_list',   $order_list['result']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);

    /* 显示模板 */
    assign_query_info();

    $smarty->display('settlement_stats.dwt');
}

/*------------------------------------------------------ */
//-- 结算查询
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'settlement_stats_query')
{
    $order_list = merchants_commission_list();

    $smarty->assign('order_list',   $order_list['result']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('settlement_stats.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}

/*------------------------------------------------------ */
//-- 结算综合统计
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'settlement_total_stats')
{
    $total_stats = settlement_total_stats();
    make_json_result('', '', $total_stats);
}

/*------------------------------------------------------ */
//-- 余额
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'balance_stats')
{
    $smarty->assign('total_stats', balance_total_stats());

    $order_list = balance_stats();
    
    $smarty->assign('full_page',    1);
    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);

    /* 显示模板 */
    assign_query_info();
    
    $smarty->display('balance_stats.dwt');
}

/*------------------------------------------------------ */
//-- 余额查询
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'balance_stats_query')
{
    $order_list = balance_stats();

    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('balance_stats.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}

/*------------------------------------------------------ */
//-- 结算综合统计
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'balance_total_stats')
{
    $total_stats = balance_total_stats();
    make_json_result('', '', $total_stats);
}

/*------------------------------------------------------ */
//-- 导出余额
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'download')
{ 
    $_GET['uselastfilter'] = 1;
    $order_list = member_account_stats();
    $tdata = $order_list['orders'];
    $thead = array($_LANG['record_id'], $_LANG['user_name'], $_LANG['finance_analysis'][5], $_LANG['finance_analysis'][6], $_LANG['finance_analysis'][7], $_LANG['finance_analysis'][8], $_LANG['finance_analysis'][9]);
    $tbody = array('user_id', 'user_name', 'recharge_money', 'consumption_money', 'cash_money', 'return_money', 'user_money');

    $config = array(
        'filename' => $_LANG['balance_stats'],
        'thead' => $thead,
        'tbody' => $tbody,
        'tdata' => $tdata
    );
    list_download($config);
}

/*------------------------------------------------------ */
//-- 导出结算
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'download_settlement')
{ 
    $_GET['uselastfilter'] = 1;
    $order_list = merchants_commission_list();
    $tdata = $order_list['result'];
    $thead = array($_LANG['record_id'], $_LANG['steps_shop_name'], $_LANG['finance_analysis'][1], $_LANG['finance_analysis'][2], $_LANG['finance_analysis'][3], $_LANG['finance_analysis'][4]);
    $tbody = array('user_id', 'store_name', 'valid_total', 'refund_total', 'platform_commission', 'is_settlement');

    $config = array(
        'filename' => $_LANG['settlement_stats'],
        'thead' => $thead,
        'tbody' => $tbody,
        'tdata' => $tdata
    );
    list_download($config);
}

?>