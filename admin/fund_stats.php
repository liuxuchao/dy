<?php

/**
 * dsc 资金统计管理
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
//-- 销售走势
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'summary_of_money')
{
    /* 时间参数 */
    $today      = local_strtotime(local_date('Y-m-d'));
    $start_date = $today;
    $end_date   = $today + 86400;
    
    $search_data = array();
    $search_data['start_date'] = $start_date;
    $search_data['end_date'] = $end_date;
    $today_sale = get_statistical_today_sale($search_data);
    $smarty->assign('today_sale', $today_sale);
    $smarty->assign('update_time', local_date('Y-m-d H:i:s', gmtime()));

    $smarty->display('summary_of_money.dwt');
}

/*------------------------------------------------------ */
//-- 走势异步
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'get_chart_data')
{
    /* 时间参数 */
    $today      = local_strtotime(local_date('Y-m-d'));
    $start_date = $today;
    $end_date   = $today + 86400;
    
    $search_data = array();
    $search_data['start_date'] = $start_date;
    $search_data['end_date'] = $end_date;
    $chart_data = get_statistical_today_trend($search_data);
    make_json_result($chart_data);
}

/*------------------------------------------------------ */
//-- 会员管理
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'member_account')
{
    $smarty->assign('total_stats', shop_total_stats());

    $order_list = member_account_stats();
    
    $smarty->assign('full_page',    1);
    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);

    /* 显示模板 */
    assign_query_info();

    $smarty->display('member_account.dwt');
}

/*------------------------------------------------------ */
//-- 会员管理查询
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'member_account_query')
{
    $order_list = member_account_stats();

    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('member_account.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}

/*------------------------------------------------------ */
//-- 充值管理
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'recharge_management')
{
    $smarty->display('recharge_management.dwt');
}

/*------------------------------------------------------ */
//-- 提现管理
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'cash_management')
{
    $smarty->display('cash_management.dwt');
}

/*------------------------------------------------------ */
//-- 导出
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'download')
{ 
    $_GET['uselastfilter'] = 1;
    $order_list = member_account_stats();
    $tdata = $order_list['orders'];
    $thead = array($_LANG['record_id'], $_LANG['user_desc'], $_LANG['user_rank'], '可用资金', '冻结资金');
    $tbody = array('user_id', 'user_name', 'rank_name', 'user_money', 'frozen_money');

    $config = array(
        'filename' => $_LANG['02_member_account'],
        'thead' => $thead,
        'tbody' => $tbody,
        'tdata' => $tdata
    );
    list_download($config);
}

?>